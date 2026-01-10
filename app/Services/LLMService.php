<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class LLMService
{
    /**
     * Generate a reply using LLM with strict business profile constraints
     */
    public function generateReply(Business $business, string $customerMessage, ?string $intent = null): array
    {
        if (! config('services.llm.enabled')) {
            return [
                'success' => false,
                'reply' => null,
                'escalation_needed' => true,
                'error' => 'LLM disabled',
            ];
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($business, $intent);
            $userPrompt = $this->buildUserPrompt($customerMessage);

            $response = OpenAI::chat()->create([
                'model' => config('services.llm.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => config('services.llm.max_tokens', 500),
                'temperature' => config('services.llm.temperature', 0.3),
            ]);

            $replyText = $response->choices[0]->message->content ?? '';

            // Check if escalation is needed
            $escalationNeeded = $this->detectEscalation($replyText);

            Log::info('LLM reply generated', [
                'business' => $business->name,
                'intent' => $intent,
                'tokens' => $response->usage->totalTokens ?? 0,
                'escalation' => $escalationNeeded,
            ]);

            return [
                'success' => true,
                'reply' => $replyText,
                'escalation_needed' => $escalationNeeded,
                'tokens_used' => $response->usage->totalTokens ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('LLM generation failed', [
                'error' => $e->getMessage(),
                'business' => $business->name,
            ]);

            return [
                'success' => false,
                'reply' => null,
                'escalation_needed' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build system prompt with strict business constraints
     */
    protected function buildSystemPrompt(Business $business, ?string $intent): string
    {
        $profileJson = json_encode([
            'business_name' => $business->name,
            'services' => $business->services,
            'areas' => $business->areas,
            'operating_hours' => $business->operating_hours,
            'booking_method' => $business->booking_method,
        ], JSON_PRETTY_PRINT);

        $prompt = "You are a customer support assistant for {$business->name}.\n\n";
        $prompt .= "STRICT RULES:\n";
        $prompt .= "1. ONLY answer using the business profile data provided below\n";
        $prompt .= "2. If information is not in the profile, politely say you don't have that information\n";
        $prompt .= "3. NEVER make up prices, services, areas, or hours\n";
        $prompt .= "4. NEVER answer questions outside the business scope (politics, news, general advice, etc.)\n";
        $prompt .= "5. Keep replies concise and helpful (2-3 sentences max)\n";
        $prompt .= "6. Use a friendly, professional tone\n";
        $prompt .= "7. If you cannot help, offer to connect them with the business owner\n\n";

        $prompt .= "BUSINESS PROFILE:\n";
        $prompt .= "```json\n{$profileJson}\n```\n\n";

        if ($intent) {
            $prompt .= "Customer Intent: {$intent}\n\n";
        }

        $prompt .= 'Provide a natural, helpful reply using ONLY the profile data above.';

        return $prompt;
    }

    /**
     * Build user prompt
     */
    protected function buildUserPrompt(string $customerMessage): string
    {
        return "Customer message: {$customerMessage}";
    }

    /**
     * Detect if escalation is needed based on LLM response
     */
    protected function detectEscalation(string $reply): bool
    {
        $escalationPhrases = [
            "don't have that information",
            'not available in',
            'connect you with',
            'reach out to',
            'contact the owner',
            'i cannot',
            "i can't",
            'unable to help',
            'outside my scope',
        ];

        $lowerReply = strtolower($reply);

        foreach ($escalationPhrases as $phrase) {
            if (str_contains($lowerReply, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if LLM is available and configured
     */
    public function isAvailable(): bool
    {
        return config('services.llm.enabled', false)
            && ! empty(config('openai.api_key'));
    }
}
