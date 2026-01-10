<?php

use App\Models\Business;
use App\Models\OnboardingState;
use App\Services\OnboardingService;
use App\Services\WhatsAppService;

beforeEach(function () {
    $this->whatsAppService = Mockery::mock(WhatsAppService::class);
    $this->app->instance(WhatsAppService::class, $this->whatsAppService);
    $this->onboardingService = app(OnboardingService::class);
});

test('can start onboarding process', function () {
    $phoneNumber = '1234567890';

    $this->whatsAppService->shouldReceive('sendMessage')
        ->once()
        ->with($phoneNumber, Mockery::pattern('/Welcome to Sahuti/'));

    $this->onboardingService->startOnboarding($phoneNumber);

    expect(OnboardingState::where('phone_number', $phoneNumber)->exists())->toBeTrue();
    $state = OnboardingState::where('phone_number', $phoneNumber)->first();
    expect($state->current_step)->toBe(OnboardingState::STEP_NAME);
    expect($state->is_complete)->toBeFalse();
});

test('prevents duplicate onboarding for already onboarded business', function () {
    $phoneNumber = '1234567890';

    Business::create([
        'phone_number' => $phoneNumber,
        'name' => 'Test Business',
        'services' => ['Service 1'],
        'areas' => ['Area 1'],
        'operating_hours' => ['text' => 'Mon-Fri 9AM-5PM'],
        'booking_method' => 'Call us',
        'profile_data' => [],
        'is_onboarded' => true,
    ]);

    $this->whatsAppService->shouldReceive('sendMessage')
        ->once()
        ->with($phoneNumber, Mockery::pattern('/already completed onboarding/'));

    $this->onboardingService->startOnboarding($phoneNumber);

    expect(OnboardingState::where('phone_number', $phoneNumber)->exists())->toBeFalse();
});

test('can process onboarding responses through all steps', function () {
    $phoneNumber = '1234567890';

    // Start onboarding
    $this->whatsAppService->shouldReceive('sendMessage')->times(6);

    $this->onboardingService->startOnboarding($phoneNumber);

    // Step 1: Business name
    $this->onboardingService->processResponse($phoneNumber, 'Test Business');
    $state = OnboardingState::where('phone_number', $phoneNumber)->first();
    expect($state->current_step)->toBe(OnboardingState::STEP_SERVICES);

    // Step 2: Services
    $this->onboardingService->processResponse($phoneNumber, 'Plumbing, Electrical');
    $state->refresh();
    expect($state->current_step)->toBe(OnboardingState::STEP_AREAS);

    // Step 3: Areas
    $this->onboardingService->processResponse($phoneNumber, 'New York, Brooklyn');
    $state->refresh();
    expect($state->current_step)->toBe(OnboardingState::STEP_HOURS);

    // Step 4: Hours
    $this->onboardingService->processResponse($phoneNumber, 'Mon-Fri 9AM-5PM');
    $state->refresh();
    expect($state->current_step)->toBe(OnboardingState::STEP_BOOKING);

    // Step 5: Booking method (should move to confirm and send summary)
    $this->onboardingService->processResponse($phoneNumber, 'Call us at 555-1234');
    $state->refresh();
    expect($state->current_step)->toBe(OnboardingState::STEP_CONFIRM);
    expect($state->collected_data)->toHaveKeys([
        OnboardingState::STEP_NAME,
        OnboardingState::STEP_SERVICES,
        OnboardingState::STEP_AREAS,
        OnboardingState::STEP_HOURS,
        OnboardingState::STEP_BOOKING,
    ]);
});

test('can confirm and save business profile', function () {
    $phoneNumber = '1234567890';

    OnboardingState::create([
        'phone_number' => $phoneNumber,
        'current_step' => OnboardingState::STEP_CONFIRM,
        'collected_data' => [
            OnboardingState::STEP_NAME => 'Test Business',
            OnboardingState::STEP_SERVICES => 'Plumbing, Electrical',
            OnboardingState::STEP_AREAS => 'New York, Brooklyn',
            OnboardingState::STEP_HOURS => 'Mon-Fri 9AM-5PM',
            OnboardingState::STEP_BOOKING => 'Call us',
        ],
        'is_complete' => false,
    ]);

    $this->whatsAppService->shouldReceive('sendMessage')
        ->once()
        ->with($phoneNumber, Mockery::pattern('/Onboarding Complete/'));

    $this->onboardingService->processResponse($phoneNumber, 'YES');

    expect(Business::where('phone_number', $phoneNumber)->exists())->toBeTrue();
    $business = Business::where('phone_number', $phoneNumber)->first();
    expect($business->name)->toBe('Test Business');
    expect($business->services)->toBe(['Plumbing', 'Electrical']);
    expect($business->areas)->toBe(['New York', 'Brooklyn']);
    expect($business->is_onboarded)->toBeTrue();

    $state = OnboardingState::where('phone_number', $phoneNumber)->first();
    expect($state->is_complete)->toBeTrue();
});

test('can restart onboarding with EDIT command', function () {
    $phoneNumber = '1234567890';

    OnboardingState::create([
        'phone_number' => $phoneNumber,
        'current_step' => OnboardingState::STEP_CONFIRM,
        'collected_data' => [
            OnboardingState::STEP_NAME => 'Test Business',
            OnboardingState::STEP_SERVICES => 'Service 1',
            OnboardingState::STEP_AREAS => 'Area 1',
            OnboardingState::STEP_HOURS => 'Mon-Fri',
            OnboardingState::STEP_BOOKING => 'Call us',
        ],
        'is_complete' => false,
    ]);

    $this->whatsAppService->shouldReceive('sendMessage')
        ->once()
        ->with($phoneNumber, Mockery::pattern('/start over/'));

    $this->onboardingService->processResponse($phoneNumber, 'EDIT');

    $state = OnboardingState::where('phone_number', $phoneNumber)->first();
    expect($state->current_step)->toBe(OnboardingState::STEP_NAME);
    expect($state->collected_data)->toBe([]);
    expect($state->is_complete)->toBeFalse();
});
