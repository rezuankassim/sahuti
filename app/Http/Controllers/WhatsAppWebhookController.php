<?php

namespace App\Http\Controllers;

use App\Models\AutoReplyLog;
use App\Models\Business;
use App\Models\ConversationPause;
use App\Services\AutoReplyService;
use App\Services\OnboardingService;
use App\Services\RateLimiterService;
use App\Services\TenantRouterService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService,
        protected OnboardingService $onboardingService,
        protected AutoReplyService $autoReplyService,
        protected RateLimiterService $rateLimiter,
        protected TenantRouterService $tenantRouter
    ) {}

    /**
     * Verify webhook endpoint (GET request from Meta)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe') {
            Log::warning('WhatsApp webhook verification failed - invalid mode', [
                'mode' => $mode,
            ]);

            return response('Forbidden', 403);
        }

        // Check global verify token first
        if ($token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully (global token)');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        // Check tenant-specific verify tokens
        $business = Business::where('webhook_verify_token', $token)
            ->first();

        if ($business) {
            Log::info('WhatsApp webhook verified successfully (tenant-specific)', [
                'business_id' => $business->id,
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed - token not found', [
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook (POST request from Meta)
     */
    public function handle(Request $request)
    {
        Log::info('WhatsApp webhook POST received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
        ]);
        $data = $request->all();

        // Try to determine which business this webhook is for
        $phoneNumberId = null;
        $business = null;

        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $phoneNumberId = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
            $business = $this->tenantRouter->getBusinessByPhoneNumberId($phoneNumberId);
        }

        // Verify webhook signature
        $signature = $request->header('X-Hub-Signature-256');
        if ($signature && ! $this->whatsAppService->verifyWebhookSignature($signature, $request->getContent(), $business)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return response('Unauthorized', 401);
        }

        Log::info('WhatsApp webhook received', ['data' => $data]);

        // WhatsApp sends updates for messages, message status, etc.
        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        // Handle messages
                        if ($change['field'] === 'messages' && isset($change['value']['messages'])) {
                            // Extract phone_number_id from metadata for tenant routing
                            $phoneNumberId = $change['value']['metadata']['phone_number_id'] ?? null;

                            foreach ($change['value']['messages'] as $message) {
                                $this->handleMessage($message, $phoneNumberId);
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
     * Process incoming message and handle onboarding or send auto-reply
     */
    protected function handleMessage(array $messageData, ?string $phoneNumberId = null): void
    {
        try {
            // Log incoming message
            $inboundMessage = $this->whatsAppService->handleIncomingMessage($messageData);

            if (! $inboundMessage) {
                Log::error('Failed to log inbound message');

                return;
            }

            Log::info('Inbound message logged', ['message_id' => $inboundMessage->message_id]);

            $from = $messageData['from'];
            $messageType = $messageData['type'];

            // Only process text messages
            if ($messageType !== 'text') {
                return;
            }

            $messageText = $messageData['text']['body'] ?? '';

            // Route to correct business tenant for onboarding
            $business = null;
            if ($phoneNumberId) {
                $business = $this->tenantRouter->getBusinessByPhoneNumberIdWithFallback($phoneNumberId);
            }

            // Check if user wants to start onboarding
            if (strtoupper(trim($messageText)) === 'ONBOARDING') {
                $this->onboardingService->startOnboarding($from, $business);

                return;
            }

            // Check if user has an active onboarding state
            if ($this->onboardingService->hasActiveOnboarding($from)) {
                $this->onboardingService->processResponse($from, $messageText, $business);

                return;
            }

            // Check if conversation is paused (human takeover)
            if (ConversationPause::isPaused($from)) {
                Log::info('Conversation paused, skipping auto-reply', ['from' => $from]);

                return;
            }

            // Check burst detection (prevent replying to rapid consecutive messages)
            if ($this->rateLimiter->isRateLimited($from)) {
                Log::info('Customer in burst mode, skipping auto-reply (will reply after burst)', [
                    'from' => $from,
                    'burst_window_remaining' => $this->rateLimiter->getRemainingCooldown($from),
                ]);

                return;
            }

            // Ensure business is set (may have been set earlier for onboarding)
            if (! $business) {
                if (! $phoneNumberId) {
                    // Fallback: try to extract from message metadata
                    $phoneNumberId = $messageData['metadata']['phone_number_id'] ?? config('services.whatsapp.phone_number_id');
                }

                if (! $phoneNumberId) {
                    Log::warning('No phone_number_id available for routing');

                    return;
                }

                // Use tenant router with fallback for single-tenant setups
                $business = $this->tenantRouter->getBusinessByPhoneNumberIdWithFallback($phoneNumberId);
            }

            if (! $business) {
                Log::warning('No business found for phone_number_id', [
                    'phone_number_id' => $phoneNumberId,
                ]);

                return;
            }

            if (! $business->is_onboarded) {
                Log::warning('Business not onboarded, skipping auto-reply', [
                    'business_id' => $business->id,
                ]);

                return;
            }

            // Generate and send auto-reply
            $startTime = microtime(true);
            $replyMessage = $this->autoReplyService->generateReply($messageText, $business);
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Determine reply type
            $replyType = $this->determineReplyType($messageText, $replyMessage);

            if ($replyMessage) {
                $outboundMessage = $this->whatsAppService->sendMessage($from, $replyMessage, $business);

                if ($outboundMessage) {
                    // Set rate limit cooldown
                    $this->rateLimiter->setCooldown($from);

                    // Log reply
                    AutoReplyLog::create([
                        'customer_phone' => $from,
                        'business_id' => $business->id,
                        'message_text' => $messageText,
                        'reply_text' => $replyMessage,
                        'reply_type' => $replyType,
                        'rate_limited' => false,
                        'duration_ms' => $duration,
                    ]);

                    Log::info('Auto-reply sent successfully', [
                        'customer_phone' => $from,
                        'business' => $business->name,
                        'message_id' => $outboundMessage->message_id,
                        'reply_type' => $replyType,
                        'duration_ms' => $duration,
                    ]);
                } else {
                    Log::error('Failed to send auto-reply', ['to' => $from]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine reply type from message and reply content
     */
    protected function determineReplyType(string $message, string $reply): string
    {
        // Check if after-hours
        if (str_contains($reply, 'currently closed')) {
            return 'after_hours';
        }

        // Check if fallback menu
        if (str_contains($reply, 'Quick menu')) {
            return 'fallback';
        }

        // Check if menu selection
        if (in_array(trim($message), ['1', '2', '3', '4'])) {
            return 'menu_selection';
        }

        // Check if escalation
        if (str_contains($reply, 'personal response')) {
            return 'escalation';
        }

        // Check if rule-based (contains emoji markers)
        if (str_contains($reply, '💰') || str_contains($reply, '📍') ||
            str_contains($reply, '🕐') || str_contains($reply, '📅')) {
            return 'rule';
        }

        // Otherwise assume LLM
        return 'llm';
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
