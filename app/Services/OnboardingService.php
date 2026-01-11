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
    public function startOnboarding(string $phoneNumber, ?Business $business = null): void
    {
        // Check if already onboarded
        $existingBusiness = Business::where('phone_number', $phoneNumber)->first();
        if ($existingBusiness) {
            $this->whatsAppService->sendMessage(
                $phoneNumber,
                "You've already completed onboarding! Your business '{$existingBusiness->name}' is registered.",
                $business
            );

            return;
        }

        // If business is provided (tenant-specific onboarding), check if onboarding is locked
        if ($business) {
            if ($business->isOnboardingLocked() && ! $business->canOnboard($phoneNumber)) {
                // Silently ignore - different number trying to onboard
                Log::info('Onboarding attempt blocked - different number', [
                    'phone_number' => $phoneNumber,
                    'onboarding_phone' => $business->onboarding_phone,
                ]);

                return;
            }

            // Lock onboarding to this phone number if not already locked
            if (! $business->isOnboardingLocked()) {
                $business->update(['onboarding_phone' => $phoneNumber]);
                Log::info('Onboarding locked to phone number', [
                    'business_id' => $business->id,
                    'phone_number' => $phoneNumber,
                ]);
            }
        }

        // Check if onboarding already in progress
        $existingState = OnboardingState::where('phone_number', $phoneNumber)
            ->where('is_complete', false)
            ->first();

        if ($existingState) {
            $this->whatsAppService->sendMessage(
                $phoneNumber,
                "You already have an onboarding in progress. Let's continue from where we left off.\n\n".$this->getPromptForStep($existingState->current_step),
                $business
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
            $this->getPromptForStep(OnboardingState::STEP_NAME),
            $business
        );

        Log::info('Onboarding started', [
            'phone_number' => $phoneNumber,
            'business_id' => $business?->id,
        ]);
    }

    /**
     * Process user response based on current onboarding step
     */
    public function processResponse(string $phoneNumber, string $message, ?Business $business = null): void
    {
        $state = OnboardingState::where('phone_number', $phoneNumber)
            ->where('is_complete', false)
            ->first();

        if (! $state) {
            Log::warning('No active onboarding state found', ['phone_number' => $phoneNumber]);

            return;
        }

        // Check if this number is allowed to onboard (tenant-specific check)
        if ($business && ! $business->canOnboard($phoneNumber)) {
            Log::info('Onboarding response blocked - different number', [
                'phone_number' => $phoneNumber,
                'onboarding_phone' => $business->onboarding_phone,
            ]);

            return;
        }

        // Handle confirmation step
        if ($state->current_step === OnboardingState::STEP_CONFIRM) {
            $this->handleConfirmation($state, $message, $business);

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

            $this->whatsAppService->sendMessage($phoneNumber, $summary, $business);
        } else {
            // Update state and send next question
            $state->update([
                'collected_data' => $collectedData,
                'current_step' => $nextStep,
            ]);

            $this->whatsAppService->sendMessage(
                $phoneNumber,
                $this->getPromptForStep($nextStep),
                $business
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
            OnboardingState::STEP_SERVICES => "Great! What services do you offer?\n\n(Format: Service Name - Price)\n\nExample:\nCleaning - 50\nDeep Clean - 100\n\nList one per line.",
            OnboardingState::STEP_AREAS => "Which areas do you cover?\n\n(List areas separated by commas)\n\nExample: Kuala Lumpur, Petaling Jaya, Selangor",
            OnboardingState::STEP_HOURS => "What are your operating hours?\n\n(Format: Day: Start-End)\n\nExample:\nMonday: 09:00-18:00\nTuesday: 09:00-18:00\nSaturday: Closed\n\nList each day on a new line.",
            OnboardingState::STEP_BOOKING => 'How should customers book appointments with you?\n\nExample: Call us at 012-345-6789 or WhatsApp to book!',
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
        $servicesText = $data[OnboardingState::STEP_SERVICES] ?? 'N/A';
        $areasText = $data[OnboardingState::STEP_AREAS] ?? 'N/A';
        $hoursText = $data[OnboardingState::STEP_HOURS] ?? 'N/A';
        $booking = $data[OnboardingState::STEP_BOOKING] ?? 'N/A';

        // Format services for display
        $services = str_replace("\n", "\n• ", $servicesText);
        if (! str_starts_with($services, '•')) {
            $services = "• {$services}";
        }

        // Format areas for display
        $areas = $areasText;

        // Format hours for display
        $hours = str_replace("\n", "\n• ", $hoursText);
        if (! str_starts_with($hours, '•')) {
            $hours = "• {$hours}";
        }

        return "📋 *Business Profile Summary*\n\n".
            "🏢 *Business Name:* {$name}\n\n".
            "💼 *Services:*\n{$services}\n\n".
            "📍 *Coverage Areas:* {$areas}\n\n".
            "🕐 *Operating Hours:*\n{$hours}\n\n".
            "📅 *Booking Method:* {$booking}\n\n".
            "---\n\n".
            "Is this correct?\n\n".
            'Reply *YES* to confirm or *EDIT* to start over.';
    }

    /**
     * Handle YES/EDIT confirmation
     */
    protected function handleConfirmation(OnboardingState $state, string $message, ?Business $business = null): void
    {
        $response = strtoupper(trim($message));

        if ($response === 'YES') {
            $this->saveBusinessProfile($state->phone_number, $state->collected_data, $business);
            $state->update(['is_complete' => true]);

            $this->whatsAppService->sendMessage(
                $state->phone_number,
                "✅ *Onboarding Complete!*\n\n".
                'Your business profile has been successfully saved. Welcome to Sahuti! 🎉',
                $business
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
                "No problem! Let's start over.\n\n".$this->getPromptForStep(OnboardingState::STEP_NAME),
                $business
            );

            Log::info('Onboarding restarted', ['phone_number' => $state->phone_number]);
        } else {
            $this->whatsAppService->sendMessage(
                $state->phone_number,
                'Please reply with *YES* to confirm or *EDIT* to start over.',
                $business
            );
        }
    }

    /**
     * Save business profile to database
     */
    protected function saveBusinessProfile(string $phoneNumber, array $data, ?Business $business = null): void
    {
        // Parse services with prices (Format: "Service Name - Price")
        $servicesText = $data[OnboardingState::STEP_SERVICES] ?? '';
        $services = [];
        foreach (explode("\n", $servicesText) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            // Parse "Service Name - Price"
            if (preg_match('/^(.+?)\s*-\s*(.+)$/', $line, $matches)) {
                $services[] = [
                    'name' => trim($matches[1]),
                    'price' => trim($matches[2]),
                ];
            }
        }

        // Parse areas from comma-separated strings
        $areas = array_map('trim', explode(',', $data[OnboardingState::STEP_AREAS] ?? ''));
        $areas = array_filter($areas); // Remove empty values

        // Parse operating hours (Format: "Day: HH:MM-HH:MM" or "Day: Closed")
        $hoursText = $data[OnboardingState::STEP_HOURS] ?? '';
        $operatingHours = [];
        foreach (explode("\n", $hoursText) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            // Parse "Day: HH:MM-HH:MM" or "Day: Closed"
            if (preg_match('/^(\w+)\s*:\s*(.+)$/i', $line, $matches)) {
                $day = strtolower(trim($matches[1]));
                $time = trim($matches[2]);

                if (stripos($time, 'closed') !== false) {
                    $operatingHours[$day] = ['closed' => true];
                } elseif (preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $time, $timeMatches)) {
                    $operatingHours[$day] = [
                        'open' => $timeMatches[1],
                        'close' => $timeMatches[2],
                    ];
                }
            }
        }

        // If business already exists (tenant onboarding), update it
        if ($business) {
            $business->update([
                'phone_number' => $phoneNumber,
                'name' => $data[OnboardingState::STEP_NAME] ?? '',
                'services' => $services,
                'areas' => array_values($areas),
                'operating_hours' => $operatingHours,
                'booking_method' => $data[OnboardingState::STEP_BOOKING] ?? '',
                'profile_data' => $data,
                'is_onboarded' => true,
            ]);
        } else {
            // Create new business for global onboarding
            Business::create([
                'phone_number' => $phoneNumber,
                'name' => $data[OnboardingState::STEP_NAME] ?? '',
                'services' => $services,
                'areas' => array_values($areas),
                'operating_hours' => $operatingHours,
                'booking_method' => $data[OnboardingState::STEP_BOOKING] ?? '',
                'profile_data' => $data,
                'is_onboarded' => true,
            ]);
        }

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
