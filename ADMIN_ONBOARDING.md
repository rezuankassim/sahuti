# Admin Onboarding via WhatsApp

This feature allows business owners to onboard their business through WhatsApp using a conversational flow.

## How It Works

### Starting Onboarding

1. Send the message **"ONBOARDING"** to your WhatsApp Business number
2. The system will guide you through 5 questions
3. After answering all questions, you'll see a summary
4. Confirm with **YES** or restart with **EDIT**

### Onboarding Flow

The onboarding process consists of the following steps:

1. **Business Name** - "What's your business name?"
2. **Services** - "What services do you offer? (comma-separated)"
3. **Coverage Areas** - "Which areas do you cover? (comma-separated)"
4. **Operating Hours** - "What are your operating hours?"
5. **Booking Method** - "How should customers book appointments?"
6. **Confirmation** - Review summary and confirm with YES or EDIT

### Example Conversation

```
You: ONBOARDING

Bot: Welcome to Sahuti! 🎉

Let's get your business set up. What's your business name?

You: ABC Plumbing Services

Bot: Great! What services do you offer?

(You can list them separated by commas)

You: Plumbing repairs, Installation, Emergency services

Bot: Which areas do you cover?

(List areas separated by commas)

You: Manhattan, Brooklyn, Queens

Bot: What are your operating hours?

(e.g., Mon-Fri 9AM-5PM, Sat 10AM-2PM)

You: Mon-Fri 8AM-6PM, Sat 9AM-3PM

Bot: How should customers book appointments with you?

You: Call us at 555-1234 or book online at our website

Bot: 📋 *Business Profile Summary*

🏢 *Business Name:* ABC Plumbing Services

💼 *Services:* Plumbing repairs, Installation, Emergency services

📍 *Coverage Areas:* Manhattan, Brooklyn, Queens

🕐 *Operating Hours:* Mon-Fri 8AM-6PM, Sat 9AM-3PM

📅 *Booking Method:* Call us at 555-1234 or book online at our website

---

Is this correct?

Reply *YES* to confirm or *EDIT* to start over.

You: YES

Bot: ✅ *Onboarding Complete!*

Your business profile has been successfully saved. Welcome to Sahuti! 🎉
```

## Features

- ✅ **Trigger-based start** - Send "ONBOARDING" to begin
- ✅ **State management** - System remembers your progress
- ✅ **Validation** - Prevents duplicate onboarding
- ✅ **Resume capability** - Continue if interrupted
- ✅ **Edit capability** - Restart if you make a mistake
- ✅ **JSON storage** - Complete profile saved as JSON

## Database Tables

### `businesses`
Stores completed business profiles:
- `phone_number` - WhatsApp phone number (unique)
- `name` - Business name
- `services` - JSON array of services
- `areas` - JSON array of coverage areas
- `operating_hours` - JSON object with hours
- `booking_method` - How customers book
- `profile_data` - Complete raw data as JSON
- `is_onboarded` - Boolean flag

### `onboarding_states`
Tracks active onboarding conversations:
- `phone_number` - WhatsApp phone number (unique)
- `current_step` - Current question (name, services, areas, hours, booking, confirm)
- `collected_data` - JSON with answers so far
- `is_complete` - Whether onboarding finished

## Technical Implementation

### Services

**`OnboardingService`** (`app/Services/OnboardingService.php`)
- `startOnboarding($phoneNumber)` - Initialize new onboarding
- `processResponse($phoneNumber, $message)` - Handle user responses
- `hasActiveOnboarding($phoneNumber)` - Check for active state
- `getPromptForStep($step)` - Get question text
- `generateSummary($data)` - Create confirmation message
- `saveBusinessProfile($phoneNumber, $data)` - Save to database

### Models

**`Business`** (`app/Models/Business.php`)
- Represents a completed business profile

**`OnboardingState`** (`app/Models/OnboardingState.php`)
- Tracks conversation state during onboarding
- Step constants: `STEP_NAME`, `STEP_SERVICES`, `STEP_AREAS`, `STEP_HOURS`, `STEP_BOOKING`, `STEP_CONFIRM`

### Webhook Integration

The `WhatsAppWebhookController` now handles:
1. Check if message is "ONBOARDING" → start onboarding
2. Check if user has active state → process response
3. Otherwise → send default "Hello" reply

## Testing

Run tests with:
```bash
php artisan test --filter=OnboardingTest
```

Tests cover:
- Starting onboarding process
- Preventing duplicate onboarding
- Processing all steps sequentially
- Confirming and saving profile
- Restarting with EDIT command

## Viewing Onboarded Businesses

You can query businesses via Tinker:

```bash
php artisan tinker
```

```php
// Get all businesses
App\Models\Business::all();

// Get specific business by phone
App\Models\Business::where('phone_number', '1234567890')->first();

// Get business profile as JSON
$business = App\Models\Business::first();
$business->profile_data; // Returns complete data
```

## Notes

- Only text messages are processed for onboarding
- Phone numbers are unique - one business per number
- Responses are trimmed but stored as-is (normalization can be added later)
- Services and areas are parsed from comma-separated values
- Operating hours stored as flexible JSON for future parsing

## Future Enhancements

- LLM-based response normalization
- More sophisticated hours parsing
- Multi-language support
- File/image upload during onboarding
- Business verification step
- Admin dashboard for viewing businesses
