# Customer Auto-Replies Feature

## Overview

The auto-reply system provides instant, rule-based responses to customer WhatsApp messages without AI risk. It detects customer intents from keywords and generates accurate replies using your business's onboarding data.

## Features

### ✅ Intent Detection
The system recognizes 4 key customer intents:

| Intent | Keywords | Response |
|--------|----------|----------|
| **PRICE** | harga, price, berapa, cost | Lists services with prices |
| **AREA** | area, kawasan, location, lokasi | Shows areas covered |
| **HOURS** | hours, time, bila, when, operating, open | Displays operating hours |
| **BOOK** | book, tempah, appointment, booking | Shows booking method |

### 🎯 Bundled Replies
- Detects multiple intents in one message (max 3 sections)
- Example: "What's your price and areas?" → Gets both price list and area coverage

### 🌙 After-Hours Gate
- Automatically detects when messages arrive outside business hours
- Sends friendly closed message with operating hours
- Uses your `operating_hours` data from onboarding

### 🤝 Human Takeover
- When business owner replies manually, bot pauses for **30 minutes**
- Prevents bot from interrupting human conversations
- Auto-resumes after 30 minutes

## Usage

### For Customers
Just send a message with your question:
- "harga?" → Gets service prices
- "What areas do you cover?" → Gets area list
- "When are you open?" → Gets operating hours
- "How to book?" → Gets booking instructions

### For Business Owners

#### Manual Reply (Triggers Pause)
```php
use App\Services\WhatsAppService;

$whatsappService = app(WhatsAppService::class);
$whatsappService->sendManualReply('60123456789', 'Thank you! I will call you shortly.');
// Bot pauses for 30 minutes for this customer
```

#### Automated Reply (No Pause)
The bot automatically handles incoming messages based on keywords. No action needed!

## Technical Details

### Database Schema

**conversation_pauses** table:
- `phone_number` - Customer's phone number
- `paused_until` - Timestamp when pause expires (30 min from manual reply)

### Models

**ConversationPause**
```php
ConversationPause::isPaused('60123456789'); // Check if paused
ConversationPause::pauseConversation('60123456789'); // Pause for 30 min
ConversationPause::cleanupExpired(); // Remove expired pauses (maintenance)
```

### Services

**AutoReplyService**
- `generateReply($message, $business)` - Main method to generate reply
- Detects intents from customer message
- Checks operating hours
- Bundles max 3 reply sections
- Returns formatted WhatsApp message

**WhatsAppService**
- `sendMessage($to, $message)` - Send automated reply
- `sendManualReply($to, $message)` - Send manual reply (triggers pause)

### Webhook Flow

1. Customer sends message → WhatsApp webhook
2. Check if conversation is paused → Skip if paused
3. Check if business is onboarded → Skip if not
4. Generate auto-reply using `AutoReplyService`
5. Send reply via `WhatsAppService`
6. Log outbound message

## Configuration

Ensure your business has completed onboarding with:
- ✅ Services (with prices)
- ✅ Areas covered
- ✅ Operating hours
- ✅ Booking method

## Testing

Run auto-reply tests:
```bash
php artisan test --filter=AutoReplyTest
```

Tests cover:
- ✅ Keyword detection for all intents
- ✅ Bundled replies (multiple intents)
- ✅ After-hours responses
- ✅ Human takeover pause (30 min)
- ✅ Pause expiration and resume

## Example Conversations

### Price Inquiry
**Customer:** "harga?"  
**Bot:** 
```
💰 *Our Services & Prices:*
• Cleaning: RM50
• Deep Clean: RM100
```

### Multiple Intents
**Customer:** "What's your price and which areas?"  
**Bot:** 
```
💰 *Our Services & Prices:*
• Cleaning: RM50
• Deep Clean: RM100

📍 *Areas We Cover:*
Kuala Lumpur, Petaling Jaya, Selangor
```

### After Hours
**Customer:** "harga?" (sent at 11 PM)  
**Bot:** 
```
🌙 Thank you for your message!

We're currently closed. Our operating hours are:

• Monday: 09:00 - 18:00
• Tuesday: 09:00 - 18:00
...

We'll get back to you during business hours!
```

### Human Takeover
**Owner:** Manually replies "Thanks for asking! Let me check..."  
**System:** Bot pauses for 30 minutes  
**Customer:** Sends another message  
**Bot:** (Silent - pause active)  
**After 30 min:** Bot resumes automatic replies

## Deliverables ✅

- [x] Customer asks "harga?" → Gets correct price reply
- [x] Owner replies manually → Bot pauses for 30 minutes
- [x] Keyword detection for PRICE, AREA, HOURS, BOOK
- [x] Bundled replies (max 3 sections)
- [x] After-hours gate with operating hours check
- [x] Human takeover pause mechanism
- [x] Comprehensive test coverage

## Files Changed

- `database/migrations/2026_01_10_103619_create_conversation_pauses_table.php`
- `app/Models/ConversationPause.php`
- `app/Services/AutoReplyService.php`
- `app/Services/WhatsAppService.php` (added `sendManualReply` method)
- `app/Http/Controllers/WhatsAppWebhookController.php`
- `tests/Feature/AutoReplyTest.php`
