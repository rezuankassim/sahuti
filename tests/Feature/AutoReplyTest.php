<?php

use App\Models\Business;
use App\Models\ConversationPause;
use App\Models\WhatsAppMessage;
use App\Services\AutoReplyService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set test config
    config([
        'services.whatsapp.phone_number_id' => '60111111111',
        'services.whatsapp.access_token' => 'test_token',
        'services.whatsapp.app_secret' => 'test_secret',
        'services.whatsapp.verify_token' => 'test_verify',
    ]);

    // Mock WhatsApp API responses
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [['id' => 'test_message_id']],
        ], 200),
    ]);

    // Create a test business
    $this->business = Business::create([
        'phone_number' => '60111111111',
        'name' => 'Test Business',
        'services' => [
            ['name' => 'Cleaning', 'price' => '50'],
            ['name' => 'Deep Clean', 'price' => '100'],
        ],
        'areas' => ['Kuala Lumpur', 'Petaling Jaya', 'Selangor'],
        'operating_hours' => [
            'monday' => ['open' => '00:00', 'close' => '23:59'],
            'tuesday' => ['open' => '00:00', 'close' => '23:59'],
            'wednesday' => ['open' => '00:00', 'close' => '23:59'],
            'thursday' => ['open' => '00:00', 'close' => '23:59'],
            'friday' => ['open' => '00:00', 'close' => '23:59'],
            'saturday' => ['open' => '00:00', 'close' => '23:59'],
            'sunday' => ['open' => '00:00', 'close' => '23:59'],
        ],
        'booking_method' => 'Call us at 012-345-6789 or WhatsApp to book!',
        'profile_data' => [],
        'is_onboarded' => true,
    ]);
});

test('customer asking about price gets correct reply', function () {
    $webhookData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'msg_001',
                                    'from' => '60123456789',
                                    'type' => 'text',
                                    'text' => ['body' => 'harga?'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $webhookData);

    $response->assertStatus(200);

    // Check that outbound message was logged
    $outboundMessage = WhatsAppMessage::where('direction', 'outbound')
        ->where('to', '60123456789')
        ->latest()
        ->first();

    expect($outboundMessage)->not->toBeNull();
    expect($outboundMessage->content['body'])->toContain('Services & Prices');
    expect($outboundMessage->content['body'])->toContain('Cleaning: RM50');
    expect($outboundMessage->content['body'])->toContain('Deep Clean: RM100');
});

test('customer asking about area gets correct reply', function () {
    $webhookData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'msg_002',
                                    'from' => '60123456789',
                                    'type' => 'text',
                                    'text' => ['body' => 'Which areas do you cover?'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $webhookData);

    $response->assertStatus(200);

    $outboundMessage = WhatsAppMessage::where('direction', 'outbound')
        ->where('to', '60123456789')
        ->latest()
        ->first();

    expect($outboundMessage)->not->toBeNull();
    expect($outboundMessage->content['body'])->toContain('Areas We Cover');
    expect($outboundMessage->content['body'])->toContain('Kuala Lumpur');
    expect($outboundMessage->content['body'])->toContain('Petaling Jaya');
});

test('bundled reply with multiple intents', function () {
    $webhookData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'msg_003',
                                    'from' => '60123456789',
                                    'type' => 'text',
                                    'text' => ['body' => 'What is your price and which areas do you cover?'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $webhookData);

    $response->assertStatus(200);

    $outboundMessage = WhatsAppMessage::where('direction', 'outbound')
        ->where('to', '60123456789')
        ->latest()
        ->first();

    expect($outboundMessage)->not->toBeNull();
    expect($outboundMessage->content['body'])->toContain('Services & Prices');
    expect($outboundMessage->content['body'])->toContain('Areas We Cover');
});

test('owner manual reply pauses bot for 30 minutes', function () {
    $whatsappService = app(WhatsAppService::class);
    $customerPhone = '60123456789';

    // Owner sends manual reply
    $whatsappService->sendManualReply($customerPhone, 'Thank you for your interest!');

    // Check that conversation is paused
    expect(ConversationPause::isPaused($customerPhone))->toBeTrue();

    // Simulate customer reply during pause
    $webhookData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'msg_004',
                                    'from' => $customerPhone,
                                    'type' => 'text',
                                    'text' => ['body' => 'harga?'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $webhookData);

    $response->assertStatus(200);

    // Should not send auto-reply during pause
    $autoReplyCount = WhatsAppMessage::where('direction', 'outbound')
        ->where('to', $customerPhone)
        ->where('created_at', '>', now()->subMinutes(1))
        ->count();

    // Only 1 outbound message (the manual reply, not auto-reply)
    expect($autoReplyCount)->toBe(1);
});

test('bot resumes after pause expires', function () {
    $customerPhone = '60123456789';

    // Create expired pause
    ConversationPause::create([
        'phone_number' => $customerPhone,
        'paused_until' => now()->subMinutes(1),
    ]);

    // Check that conversation is not paused
    expect(ConversationPause::isPaused($customerPhone))->toBeFalse();

    // Customer sends message
    $webhookData = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'id' => 'msg_005',
                                    'from' => $customerPhone,
                                    'type' => 'text',
                                    'text' => ['body' => 'harga?'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhook/whatsapp', $webhookData);

    $response->assertStatus(200);

    // Should send auto-reply since pause expired
    $outboundMessage = WhatsAppMessage::where('direction', 'outbound')
        ->where('to', $customerPhone)
        ->latest()
        ->first();

    expect($outboundMessage)->not->toBeNull();
    expect($outboundMessage->content['body'])->toContain('Services & Prices');
});

test('after-hours message gets closed reply', function () {
    $autoReplyService = app(AutoReplyService::class);

    // Mock a business with hours (assuming test runs outside these hours)
    $business = Business::create([
        'phone_number' => '60987654321',
        'name' => 'After Hours Test',
        'services' => [['name' => 'Service', 'price' => '50']],
        'areas' => ['Test Area'],
        'operating_hours' => [
            strtolower(now()->format('l')) => ['open' => '00:00', 'close' => '00:01'],
        ],
        'booking_method' => 'Book via WhatsApp',
        'profile_data' => [],
        'is_onboarded' => true,
    ]);

    $reply = $autoReplyService->generateReply('harga?', $business);

    expect($reply)->toContain('currently closed');
    expect($reply)->toContain('operating hours');
    expect($reply)->toContain('business hours');
});

test('no recognized intent sends generic greeting', function () {
    $autoReplyService = app(AutoReplyService::class);

    $reply = $autoReplyService->generateReply('Hello there!', $this->business);

    expect($reply)->toContain('How can we help you');
    expect($reply)->toContain('services and prices');
    expect($reply)->toContain('Areas we cover');
});
