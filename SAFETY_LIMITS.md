# Safety, Limits & Polish

## Overview

Comprehensive safety measures to prevent spam behavior and ensure stable operation for real users. The system implements rate limiting, fallback menus, comprehensive logging, and fail-safes.

## Implemented Protections

### ✅ 1. Rate Limiting (Max 1 reply / 90 seconds)

**Protection:** Prevents overwhelming customers with rapid-fire auto-replies.

**Implementation:**
- Per-customer cooldown tracked in cache (Redis) and database
- 90-second cooldown after each auto-reply
- Rate-limited requests are logged but no reply sent
- Cache-first for performance, database fallback

**Configuration:**
```env
AUTO_REPLY_COOLDOWN_SECONDS=90  # Default: 90 seconds
```

**Usage:**
```php
$rateLimiter->isRateLimited($customerPhone);  // Check if rate limited
$rateLimiter->setCooldown($customerPhone);     // Set 90-sec cooldown
$rateLimiter->getRemainingCooldown($customerPhone);  // Get seconds remaining
```

### ✅ 2. Single LLM Call Per Message

**Protection:** Prevents retry loops and excessive API calls.

**Implementation:**
- By design: No retry logic in LLMService
- Single call per customer message
- Timeout handled by OpenAI client
- Error returns escalation flag (no retry)

### ✅ 3. Fallback Menu

**Protection:** Provides guided experience when LLM fails or is unavailable.

**Implementation:**
```
Thank you for your message!

Quick menu:
1️⃣ Services & Prices
2️⃣ Coverage Areas
3️⃣ Operating Hours
4️⃣ How to Book

Reply with a number or ask your question.
```

**Trigger Conditions:**
- LLM disabled globally
- Business has `llm_enabled=false`
- LLM API error
- Rate limited (future enhancement)

**Menu Responses:**
- Customer replies with `1`, `2`, `3`, or `4`
- System returns corresponding info from business profile
- Uses rule-based replies (no LLM call)

### ✅ 4. Comprehensive Logging

**All auto-replies logged to `auto_reply_logs` table:**
```php
[
    'customer_phone' => '60123456789',
    'business_id' => 1,
    'message_text' => 'What is your price?',
    'reply_text' => '💰 *Our Services & Prices:*...',
    'reply_type' => 'rule|llm|fallback|after_hours|menu_selection|escalation',
    'llm_tokens_used' => 45,  // nullable
    'rate_limited' => false,
    'duration_ms' => 234,
    'created_at' => timestamp
]
```

**Log messages in application logs:**
```php
Log::info('Auto-reply sent successfully', [
    'customer_phone' => $phone,
    'business' => $business->name,
    'message_id' => $messageId,
    'reply_type' => $replyType,
    'duration_ms' => $duration,
]);

Log::info('Customer rate limited, skipping auto-reply', [
    'from' => $phone,
    'cooldown_remaining' => $remaining,
]);
```

## Safety Flow

```
Customer Message
    ↓
Onboarding check (ONBOARDING keyword)
    ↓
Active onboarding state?
    ↓
Human takeover pause (30 min)
    ↓
Rate limit check (90 sec cooldown) ← NEW
    ↓ Rate limited? → Log & skip
    ↓
Business onboarded check
    ↓
Menu selection (1-4)? ← NEW
    ↓ YES → Rule-based menu reply
    ↓ NO  → Continue
    ↓
After-hours check
    ↓
Keyword detection (PRICE, AREA, HOURS, BOOK)
    ↓ Match? → Rule-based reply
    ↓ No match → LLM reply (if enabled)
        ↓ LLM error? → Fallback menu ← NEW
        ↓ Escalation? → Human handoff message
        ↓ Success → Natural LLM reply
    ↓
Send reply + Set cooldown ← NEW
    ↓
Log to database ← NEW
```

## Database Schema

### auto_reply_logs
```sql
CREATE TABLE auto_reply_logs (
    id BIGINT PRIMARY KEY,
    customer_phone VARCHAR INDEX,
    business_id BIGINT FOREIGN KEY,
    message_text TEXT,
    reply_text TEXT NULLABLE,
    reply_type VARCHAR,  -- rule, llm, fallback, after_hours, etc.
    llm_tokens_used INT NULLABLE,
    rate_limited BOOLEAN DEFAULT FALSE,
    duration_ms INT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX (business_id, created_at),
    INDEX (customer_phone, created_at)
);
```

## Monitoring Queries

### Total Auto-Replies by Business
```php
AutoReplyLog::where('business_id', $businessId)
    ->where('created_at', '>=', now()->subDays(7))
    ->count();
```

### Rate Limit Hits
```php
AutoReplyLog::where('business_id', $businessId)
    ->where('rate_limited', true)
    ->whereBetween('created_at', [now()->subDay(), now()])
    ->count();
```

### Reply Type Distribution
```php
AutoReplyLog::where('business_id', $businessId)
    ->select('reply_type', DB::raw('count(*) as count'))
    ->groupBy('reply_type')
    ->get();
```

### Average Response Time
```php
AutoReplyLog::where('business_id', $businessId)
    ->avg('duration_ms');
```

### LLM Usage Stats
```php
AutoReplyLog::where('business_id', $businessId)
    ->where('reply_type', 'llm')
    ->sum('llm_tokens_used');
```

## Configuration

### Environment Variables
```env
# Rate Limiting
AUTO_REPLY_COOLDOWN_SECONDS=90

# LLM Monitoring (future use)
LLM_FAILURE_THRESHOLD=0.5
LLM_FAILURE_WINDOW_MINUTES=60
```

### Config File
`config/auto_reply.php`:
```php
return [
    'cooldown_seconds' => (int) env('AUTO_REPLY_COOLDOWN_SECONDS', 90),
    'llm_failure_threshold' => (float) env('LLM_FAILURE_THRESHOLD', 0.5),
    'llm_failure_window_minutes' => (int) env('LLM_FAILURE_WINDOW_MINUTES', 60),
];
```

## Testing Scenarios

### Scenario 1: Rate Limiting
```
1. Customer sends: "harga?"
2. Bot replies with price list
3. Customer immediately sends: "area?"
4. Bot SKIPS (rate limited)
5. Wait 90 seconds
6. Customer sends: "area?"
7. Bot replies with areas list
```

### Scenario 2: Fallback Menu
```
1. LLM disabled or API error
2. Customer sends: "Tell me about your business"
3. Bot replies with fallback menu
4. Customer sends: "1"
5. Bot replies with services & prices
```

### Scenario 3: Menu Navigation
```
1. Customer sends any non-keyword message
2. Bot can reply with menu (if configured)
3. Customer replies: "1" → Services & Prices
4. Customer replies: "2" → Coverage Areas
5. Customer replies: "3" → Operating Hours
6. Customer replies: "4" → How to Book
```

## Safety Guarantees

### ✅ No Spam Behavior
- **Maximum 1 reply per 90 seconds per customer**
- Rate limit tracked per customer phone number
- Cooldown enforced even if customer sends multiple messages

### ✅ Stable Operation
- Single LLM call (no retry loops)
- Fallback to menu if LLM fails
- Comprehensive error handling
- All exceptions caught and logged

### ✅ Production Ready
- Cache-based rate limiting for performance
- Database logging for audit trail
- Monitoring queries ready
- Configuration via environment variables

## Deliverables ✅

- [x] Max 1 auto reply / 90 seconds
- [x] Max 1 LLM call per message (by design)
- [x] Fallback menu if AI fails
- [x] Log everything (database + application logs)
- [x] Stable demo ready
- [x] No spam behavior
- [x] Safe for real users

## Files Created/Modified

### New Files
- `app/Models/AutoReplyLog.php` - Reply tracking model
- `app/Services/RateLimiterService.php` - 90-second cooldown
- `config/auto_reply.php` - Safety configuration
- `database/migrations/*_create_auto_reply_logs_table.php`
- `SAFETY_LIMITS.md` - This documentation

### Modified Files
- `app/Services/AutoReplyService.php` - Added fallback menu, menu selection handling
- `app/Http/Controllers/WhatsAppWebhookController.php` - Added rate limiting, comprehensive logging, AutoReplyLog creation

## Example Logs

### Successful Auto-Reply
```
[2026-01-10 15:45:12] production.INFO: Auto-reply sent successfully
{
    "customer_phone": "60123456789",
    "business": "Win Win Toys",
    "message_id": "wamid.xxx",
    "reply_type": "rule",
    "duration_ms": 156
}
```

### Rate Limited
```
[2026-01-10 15:45:42] production.INFO: Customer rate limited, skipping auto-reply
{
    "from": "60123456789",
    "cooldown_remaining": 60
}
```

### LLM Used
```
[2026-01-10 15:46:30] production.INFO: No keyword match, trying LLM
{
    "message": "Do you have teddy bears?"
}

[2026-01-10 15:46:31] production.INFO: LLM reply generated
{
    "business": "Win Win Toys",
    "intent": null,
    "reply": "Yes, we have teddy bears available! They're part of our toy collection. Would you like to know the price?",
    "tokens": 45,
    "escalation": false
}
```

## Future Enhancements

1. **Auto-disable LLM on high failure rate**
   - Track LLM failures per business
   - Auto-disable if failure rate > 50% in 1 hour
   - Send alert to business owner

2. **Dashboard Integration**
   - Reply statistics
   - Rate limit analytics
   - LLM usage metrics
   - Cost tracking

3. **Smart Cooldown**
   - Shorter cooldown (30s) for menu selections
   - Longer cooldown (2 min) after escalation
   - Adaptive based on message volume
