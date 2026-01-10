<?php

use App\Models\Business;
use App\Services\AutoReplyService;
use App\Services\LLMService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

beforeEach(function () {
    config(['services.llm.enabled' => true]);
    config(['openai.api_key' => 'test-key']);

    $this->business = Business::factory()->create([
        'name' => 'Test Cleaning Service',
        'services' => [
            ['name' => 'House Cleaning', 'price' => '80'],
            ['name' => 'Office Cleaning', 'price' => '120'],
        ],
        'areas' => ['Downtown', 'Suburbs'],
        'operating_hours' => [
            'monday' => ['open' => '00:00', 'close' => '23:59'],
            'tuesday' => ['open' => '00:00', 'close' => '23:59'],
            'wednesday' => ['open' => '00:00', 'close' => '23:59'],
            'thursday' => ['open' => '00:00', 'close' => '23:59'],
            'friday' => ['open' => '00:00', 'close' => '23:59'],
            'saturday' => ['open' => '00:00', 'close' => '23:59'],
            'sunday' => ['open' => '00:00', 'close' => '23:59'],
        ],
        'booking_method' => 'Call us at 555-1234',
        'llm_enabled' => true,
    ]);
});

test('keyword match uses rule-based reply not LLM', function () {
    $autoReplyService = app(AutoReplyService::class);

    $reply = $autoReplyService->generateReply('harga?', $this->business);

    // Should use rule-based reply
    expect($reply)->toContain('Services & Prices');
    expect($reply)->toContain('House Cleaning: RM80');
});

test('non-keyword message uses LLM when enabled', function () {
    // Mock OpenAI response
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Yes, we provide house cleaning services starting at RM80. Would you like to schedule an appointment?',
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 50,
            ],
        ]),
    ]);

    $autoReplyService = app(AutoReplyService::class);
    $reply = $autoReplyService->generateReply('Do you provide house cleaning?', $this->business);

    expect($reply)->not->toBeNull();
    expect($reply)->toContain('house cleaning');
});

test('LLM refuses to answer out-of-scope question', function () {
    // Mock LLM refusing to answer
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => "I don't have that information in my business profile. Let me connect you with the owner.",
                    ],
                ],
            ],
            'usage' => ['total_tokens' => 30],
        ]),
    ]);

    $llmService = app(LLMService::class);
    $result = $llmService->generateReply($this->business, 'What is the weather today?');

    expect($result['success'])->toBeTrue();
    expect($result['escalation_needed'])->toBeTrue();
});

test('LLM disabled falls back to generic reply', function () {
    config(['services.llm.enabled' => false]);

    $autoReplyService = app(AutoReplyService::class);
    $reply = $autoReplyService->generateReply('Tell me about your company', $this->business);

    expect($reply)->toContain('How can we help you');
    expect($reply)->toContain('services and prices');
});

test('business with llm_enabled false uses fallback', function () {
    $this->business->update(['llm_enabled' => false]);

    $autoReplyService = app(AutoReplyService::class);
    $reply = $autoReplyService->generateReply('Tell me about your services', $this->business);

    expect($reply)->toContain('How can we help you');
});

test('LLM service builds correct system prompt', function () {
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [['message' => ['content' => 'Test reply']]],
            'usage' => ['total_tokens' => 20],
        ]),
    ]);

    $llmService = app(LLMService::class);
    $result = $llmService->generateReply($this->business, 'Test message', 'PRICE');

    // Verify the result was successful
    expect($result['success'])->toBeTrue();
    expect($result['reply'])->toBe('Test reply');
});

test('LLM handles API error gracefully', function () {
    OpenAI::fake([
        new \Exception('API Error'),
    ]);

    $llmService = app(LLMService::class);
    $result = $llmService->generateReply($this->business, 'Test message');

    expect($result['success'])->toBeFalse();
    expect($result['escalation_needed'])->toBeTrue();
    expect($result['error'])->toBe('API Error');
});

test('escalation message shown when LLM cannot help', function () {
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => "I don't have that information. Let me connect you with the owner.",
                    ],
                ],
            ],
            'usage' => ['total_tokens' => 25],
        ]),
    ]);

    $autoReplyService = app(AutoReplyService::class);
    $reply = $autoReplyService->generateReply('Complex question outside scope', $this->business);

    expect($reply)->toContain('personal response');
    expect($reply)->toContain('get back to you');
});
