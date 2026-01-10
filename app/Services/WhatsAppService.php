<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ConversationPause;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $phoneNumberId;

    protected string $accessToken;

    protected string $appSecret;

    protected string $apiUrl = 'https://graph.facebook.com/v24.0';

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->appSecret = config('services.whatsapp.app_secret');
    }

    /**
     * Send a text message via WhatsApp Cloud API (automated)
     */
    public function sendMessage(string $to, string $message, ?Business $business = null): ?WhatsAppMessage
    {
        try {
            $accessToken = $business?->getWhatsAppAccessToken() ?? $this->accessToken;
            $phoneNumberId = $business?->getWhatsAppPhoneNumberId() ?? $this->phoneNumberId;

            $response = Http::withToken($accessToken)
                ->post("{$this->apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return WhatsAppMessage::create([
                    'message_id' => $data['messages'][0]['id'],
                    'direction' => 'outbound',
                    'from' => $phoneNumberId,
                    'to' => $to,
                    'message_type' => 'text',
                    'content' => ['body' => $message],
                    'status' => 'sent',
                    'metadata' => $data,
                ]);
            }

            Log::error('WhatsApp send message failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp send message exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a manual reply from business owner (triggers 30-min pause)
     */
    public function sendManualReply(string $to, string $message, ?Business $business = null): ?WhatsAppMessage
    {
        $outboundMessage = $this->sendMessage($to, $message, $business);

        if ($outboundMessage) {
            // Pause conversation for 30 minutes
            ConversationPause::pauseConversation($to);

            Log::info('Manual reply sent, conversation paused for 30 minutes', [
                'to' => $to,
                'paused_until' => now()->addMinutes(30),
            ]);
        }

        return $outboundMessage;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $signature, string $payload): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming message from webhook
     */
    public function handleIncomingMessage(array $messageData): ?WhatsAppMessage
    {
        try {
            $from = $messageData['from'];
            $messageId = $messageData['id'];
            $messageType = $messageData['type'];

            // Extract message content based on type
            $content = match ($messageType) {
                'text' => ['body' => $messageData['text']['body']],
                'image' => [
                    'id' => $messageData['image']['id'],
                    'mime_type' => $messageData['image']['mime_type'],
                    'caption' => $messageData['image']['caption'] ?? null,
                ],
                'video' => [
                    'id' => $messageData['video']['id'],
                    'mime_type' => $messageData['video']['mime_type'],
                    'caption' => $messageData['video']['caption'] ?? null,
                ],
                'audio' => [
                    'id' => $messageData['audio']['id'],
                    'mime_type' => $messageData['audio']['mime_type'],
                ],
                'document' => [
                    'id' => $messageData['document']['id'],
                    'mime_type' => $messageData['document']['mime_type'],
                    'filename' => $messageData['document']['filename'] ?? null,
                ],
                default => ['raw' => $messageData],
            };

            // Check if message already exists
            $existingMessage = WhatsAppMessage::where('message_id', $messageId)->first();
            if ($existingMessage) {
                return $existingMessage;
            }

            return WhatsAppMessage::create([
                'message_id' => $messageId,
                'direction' => 'inbound',
                'from' => $from,
                'to' => $messageData['metadata']['phone_number_id'] ?? $this->phoneNumberId,
                'message_type' => $messageType,
                'content' => $content,
                'status' => 'received',
                'metadata' => $messageData,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp handle incoming message exception', [
                'error' => $e->getMessage(),
                'data' => $messageData,
            ]);

            return null;
        }
    }
}
