<?php

namespace App\Services;

use App\Models\Business;
use App\Models\OnboardingState;
use Illuminate\Support\Facades\Log;

class OnboardingService
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Start onboarding process for a phone number
     */
    public function startOnboarding(string $phoneNumber): void
    {
        // Check if already onboarded
        $existingBusiness = Business::where('phone_number', $phoneNumber)->first();
        if ($existingBusiness) {
            $this->whatsAppService->sendMessage(
                $phoneNumber,
                "You've already completed onboarding! Your business '{$existingBusiness->name}' is registered."
            );

            return;
        }

        // Check if onboarding already in progress
        $existingState = OnboardingState::where('phone_number', $phoneNumber)
            ->where('is_complete', false)
            ->first();

        if ($existingState) {
            $this->whatsAppService->sendMessage(
                $phoneNumber,
                "You already have an onboarding in progress. Let's continue from where we left off.\n\n".$this->getPromptForStep($existingState->current_step)
            );

            return;
        }

        // Create new onboarding state
        OnboardingState::create([
            'phone_number' => $phoneNumber,
            'current_step' => OnboardingState::STEP_NAME,
            'collected_data' => [],
            'is_complete' => false,
        ]);

        // Send welcome message
        $this->whatsAppService->sendMessage(
            $phoneNumber,
            $this->getPromptForStep(OnboardingState::STEP_NAME)
        );

        Log::info('Onboarding started', ['phone_number' => $phoneNumber]);
    }

    /**
     * Process user response based on current onboarding step
     */
    public function processResponse(string $phoneNumber, string $message): void
    {
        $state = OnboardingState::where('phone_number', $phoneNumber)
            ->where('is_complete', false)
            ->first();

        if (! $state) {
            Log::warning('No active onboarding state found', ['phone_number' => $phoneNumber]);

            return;
        }

        // Handle confirmation step
        if ($state->current_step === OnboardingState::STEP_CONFIRM) {
            $this->handleConfirmation($state, $message);

            return;
        }

        // Store the response
        $collectedData = $state->collected_data;
        $collectedData[$state->current_step] = trim($message);

        // Move to next step
        $nextStep = $this->getNextStep($state->current_step);

        if ($nextStep === OnboardingState::STEP_CONFIRM) {
            // Generate and send summary
            $summary = $this->generateSummary($collectedData);
            $state->update([
                'collected_data' => $collectedData,
                'current_step' => $nextStep,
            ]);

            $this->whatsAppService->sendMessage($phoneNumber, $summary);
        } else {
            // Update state and send next question
            $state->update([
                'collected_data' => $collectedData,
                'current_step' => $nextStep,
            ]);

            $this->whatsAppService->sendMessage(
                $phoneNumber,
                $this->getPromptForStep($nextStep)
            );
        }

        Log::info('Onboarding response processed', [
            'phone_number' => $phoneNumber,
            'step' => $state->current_step,
            'next_step' => $nextStep,
        ]);
    }

    /**
     * Get the prompt message for a specific step
     */
    protected function getPromptForStep(string $step): string
    {
        return match ($step) {
            OnboardingState::STEP_NAME => "Welcome to Sahuti! 🎉\n\nLet's get your business set up. What's your business name?",
            OnboardingState::STEP_SERVICES => "Great! What services do you offer?\n\n(You can list them separated by commas)",
            OnboardingState::STEP_AREAS => "Which areas do you cover?\n\n(List areas separated by commas)",
            OnboardingState::STEP_HOURS => "What are your operating hours?\n\n(e.g., Mon-Fri 9AM-5PM, Sat 10AM-2PM)",
            OnboardingState::STEP_BOOKING => 'How should customers book appointments with you?',
            default => 'Invalid step',
        };
    }

    /**
     * Get the next step in the onboarding flow
     */
    protected function getNextStep(string $currentStep): string
    {
        return match ($currentStep) {
            OnboardingState::STEP_NAME => OnboardingState::STEP_SERVICES,
            OnboardingState::STEP_SERVICES => OnboardingState::STEP_AREAS,
            OnboardingState::STEP_AREAS => OnboardingState::STEP_HOURS,
            OnboardingState::STEP_HOURS => OnboardingState::STEP_BOOKING,
            OnboardingState::STEP_BOOKING => OnboardingState::STEP_CONFIRM,
            default => OnboardingState::STEP_CONFIRM,
        };
    }

    /**
     * Generate summary message from collected data
     */
    protected function generateSummary(array $data): string
    {
        $name = $data[OnboardingState::STEP_NAME] ?? 'N/A';
        $services = $data[OnboardingState::STEP_SERVICES] ?? 'N/A';
        $areas = $data[OnboardingState::STEP_AREAS] ?? 'N/A';
        $hours = $data[OnboardingState::STEP_HOURS] ?? 'N/A';
        $booking = $data[OnboardingState::STEP_BOOKING] ?? 'N/A';

        return "📋 *Business Profile Summary*\n\n".
            "🏢 *Business Name:* {$name}\n\n".
            "💼 *Services:* {$services}\n\n".
            "📍 *Coverage Areas:* {$areas}\n\n".
            "🕐 *Operating Hours:* {$hours}\n\n".
            "📅 *Booking Method:* {$booking}\n\n".
            "---\n\n".
            "Is this correct?\n\n".
            'Reply *YES* to confirm or *EDIT* to start over.';
    }

    /**
     * Handle YES/EDIT confirmation
     */
    protected function handleConfirmation(OnboardingState $state, string $message): void
    {
        $response = strtoupper(trim($message));

        if ($response === 'YES') {
            $this->saveBusinessProfile($state->phone_number, $state->collected_data);
            $state->update(['is_complete' => true]);

            $this->whatsAppService->sendMessage(
                $state->phone_number,
                "✅ *Onboarding Complete!*\n\n".
                'Your business profile has been successfully saved. Welcome to Sahuti! 🎉'
            );

            Log::info('Onboarding completed', ['phone_number' => $state->phone_number]);
        } elseif ($response === 'EDIT') {
            // Reset onboarding
            $state->update([
                'current_step' => OnboardingState::STEP_NAME,
                'collected_data' => [],
            ]);

            $this->whatsAppService->sendMessage(
                $state->phone_number,
                "No problem! Let's start over.\n\n".$this->getPromptForStep(OnboardingState::STEP_NAME)
            );

            Log::info('Onboarding restarted', ['phone_number' => $state->phone_number]);
        } else {
            $this->whatsAppService->sendMessage(
                $state->phone_number,
                'Please reply with *YES* to confirm or *EDIT* to start over.'
            );
        }
    }

    /**
     * Save business profile to database
     */
    protected function saveBusinessProfile(string $phoneNumber, array $data): void
    {
        // Parse services and areas from comma-separated strings
        $services = array_map('trim', explode(',', $data[OnboardingState::STEP_SERVICES] ?? ''));
        $areas = array_map('trim', explode(',', $data[OnboardingState::STEP_AREAS] ?? ''));

        Business::create([
            'phone_number' => $phoneNumber,
            'name' => $data[OnboardingState::STEP_NAME] ?? '',
            'services' => $services,
            'areas' => $areas,
            'operating_hours' => ['text' => $data[OnboardingState::STEP_HOURS] ?? ''],
            'booking_method' => $data[OnboardingState::STEP_BOOKING] ?? '',
            'profile_data' => $data,
            'is_onboarded' => true,
        ]);

        Log::info('Business profile saved', [
            'phone_number' => $phoneNumber,
            'name' => $data[OnboardingState::STEP_NAME] ?? '',
        ]);
    }

    /**
     * Check if a phone number has an active onboarding state
     */
    public function hasActiveOnboarding(string $phoneNumber): bool
    {
        return OnboardingState::where('phone_number', $phoneNumber)
            ->where('is_complete', false)
            ->exists();
    }
}
