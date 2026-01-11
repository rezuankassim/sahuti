<?php

use App\Models\Business;
use App\Models\OnboardingState;
use App\Services\OnboardingService;
use App\Services\WhatsAppService;

beforeEach(function () {
    $this->whatsappService = Mockery::mock(WhatsAppService::class);
    $this->app->instance(WhatsAppService::class, $this->whatsappService);
});

test('first phone number can start onboarding and gets locked', function () {
    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => null,
    ]);

    $phoneNumber = '+1234567890';

    expect($business->onboarding_phone)->toBeNull();
    expect($business->canOnboard($phoneNumber))->toBeTrue();

    // Mock the WhatsApp message sending
    $this->whatsappService->shouldReceive('sendMessage')->once();

    $onboardingService = app(OnboardingService::class);
    $onboardingService->startOnboarding($phoneNumber, $business);

    $business->refresh();

    expect($business->onboarding_phone)->toBe($phoneNumber);
    expect($business->isOnboardingLocked())->toBeTrue();
});

test('second phone number cannot start onboarding when locked', function () {
    $firstPhone = '+1234567890';
    $secondPhone = '+0987654321';

    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => $firstPhone,
    ]);

    expect($business->canOnboard($firstPhone))->toBeTrue();
    expect($business->canOnboard($secondPhone))->toBeFalse();

    // Should not send any message to second phone
    $this->whatsappService->shouldReceive('sendMessage')->never();

    $onboardingService = app(OnboardingService::class);
    $onboardingService->startOnboarding($secondPhone, $business);

    // Verify no onboarding state was created for second phone
    $state = OnboardingState::where('phone_number', $secondPhone)->first();
    expect($state)->toBeNull();
});

test('locked phone number can continue onboarding', function () {
    $phoneNumber = '+1234567890';

    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => $phoneNumber,
    ]);

    // Create an active onboarding state
    OnboardingState::create([
        'phone_number' => $phoneNumber,
        'current_step' => OnboardingState::STEP_NAME,
        'collected_data' => [],
        'is_complete' => false,
    ]);

    // Mock message sending
    $this->whatsappService->shouldReceive('sendMessage')->once();

    $onboardingService = app(OnboardingService::class);
    $onboardingService->processResponse($phoneNumber, 'My Business', $business);

    $state = OnboardingState::where('phone_number', $phoneNumber)->first();
    expect($state->current_step)->toBe(OnboardingState::STEP_SERVICES);
    expect($state->collected_data['name'])->toBe('My Business');
});

test('different phone number cannot continue onboarding when locked', function () {
    $firstPhone = '+1234567890';
    $secondPhone = '+0987654321';

    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => $firstPhone,
    ]);

    // Create onboarding state for first phone
    OnboardingState::create([
        'phone_number' => $firstPhone,
        'current_step' => OnboardingState::STEP_NAME,
        'collected_data' => [],
        'is_complete' => false,
    ]);

    // Second phone should not be able to process responses
    $this->whatsappService->shouldReceive('sendMessage')->never();

    $onboardingService = app(OnboardingService::class);
    $onboardingService->processResponse($secondPhone, 'Some Data', $business);

    // Verify state was not created for second phone
    $state = OnboardingState::where('phone_number', $secondPhone)->first();
    expect($state)->toBeNull();
});

test('business can be updated with tenant data on completion', function () {
    $phoneNumber = '+1234567890';

    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => $phoneNumber,
        'is_onboarded' => false,
    ]);

    $state = OnboardingState::create([
        'phone_number' => $phoneNumber,
        'current_step' => OnboardingState::STEP_CONFIRM,
        'collected_data' => [
            'name' => 'Test Business',
            'services' => 'Service 1 - 100',
            'areas' => 'Area 1, Area 2',
            'hours' => 'Monday: 09:00-17:00',
            'booking' => 'Call us',
        ],
        'is_complete' => false,
    ]);

    $this->whatsappService->shouldReceive('sendMessage')->once();

    $onboardingService = app(OnboardingService::class);
    $onboardingService->processResponse($phoneNumber, 'YES', $business);

    $business->refresh();

    expect($business->is_onboarded)->toBeTrue();
    expect($business->name)->toBe('Test Business');
    expect($business->phone_number)->toBe($phoneNumber);
});

test('resetting onboarding lock allows new phone number', function () {
    $firstPhone = '+1234567890';
    $secondPhone = '+0987654321';

    $business = Business::factory()->create([
        'phone_number_id' => '123456',
        'wa_status' => 'connected',
        'onboarding_phone' => $firstPhone,
        'is_onboarded' => true,
    ]);

    expect($business->canOnboard($secondPhone))->toBeFalse();

    // Reset onboarding
    $business->update([
        'onboarding_phone' => null,
        'is_onboarded' => false,
    ]);

    expect($business->isOnboardingLocked())->toBeFalse();
    expect($business->canOnboard($secondPhone))->toBeTrue();
});

test('business helper methods work correctly', function () {
    $phoneNumber = '+1234567890';

    $business = Business::factory()->create([
        'meta_app_secret' => 'test_secret',
        'onboarding_phone' => null,
    ]);

    expect($business->getMetaAppSecret())->toBe('test_secret');
    expect($business->isOnboardingLocked())->toBeFalse();
    expect($business->canOnboard($phoneNumber))->toBeTrue();

    $business->update(['onboarding_phone' => $phoneNumber]);

    expect($business->isOnboardingLocked())->toBeTrue();
    expect($business->canOnboard($phoneNumber))->toBeTrue();
    expect($business->canOnboard('+0987654321'))->toBeFalse();
});
