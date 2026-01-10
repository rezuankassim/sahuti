<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Verify webhook endpoint (GET request from Meta)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook (POST request from Meta)
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-Hub-Signature-256');
        if ($signature && ! $this->whatsAppService->verifyWebhookSignature($signature, $request->getContent())) {
            Log::warning('WhatsApp webhook signature verification failed');

            return response('Unauthorized', 401);
        }

        $data = $request->all();

        Log::info('WhatsApp webhook received', ['data' => $data]);

        // WhatsApp sends updates for messages, message status, etc.
        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        // Handle messages
                        if ($change['field'] === 'messages' && isset($change['value']['messages'])) {
                            foreach ($change['value']['messages'] as $message) {
                                $this->handleMessage($message);
                            }
                        }

                        // Handle message status updates
                        if ($change['field'] === 'messages' && isset($change['value']['statuses'])) {
                            foreach ($change['value']['statuses'] as $status) {
                                $this->handleStatusUpdate($status);
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Process incoming message and send auto-reply
     */
    protected function handleMessage(array $messageData): void
    {
        try {
            // Log incoming message
            $inboundMessage = $this->whatsAppService->handleIncomingMessage($messageData);

            if (! $inboundMessage) {
                Log::error('Failed to log inbound message');

                return;
            }

            Log::info('Inbound message logged', ['message_id' => $inboundMessage->message_id]);

            // Send auto-reply "Hello"
            $from = $messageData['from'];
            $outboundMessage = $this->whatsAppService->sendMessage($from, 'Hello');

            if ($outboundMessage) {
                Log::info('Auto-reply sent successfully', [
                    'to' => $from,
                    'message_id' => $outboundMessage->message_id,
                ]);
            } else {
                Log::error('Failed to send auto-reply', ['to' => $from]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle message status updates (sent, delivered, read, failed)
     */
    protected function handleStatusUpdate(array $statusData): void
    {
        try {
            $messageId = $statusData['id'];
            $status = $statusData['status'];

            $message = \App\Models\WhatsAppMessage::where('message_id', $messageId)->first();

            if ($message) {
                $message->update(['status' => $status]);
                Log::info('Message status updated', [
                    'message_id' => $messageId,
                    'status' => $status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling status update', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
