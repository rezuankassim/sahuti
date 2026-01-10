# LLM Integration for Smarter Customer Replies

## Overview

The LLM integration adds natural, context-aware replies to customer messages while preventing hallucinations through strict business profile constraints. Rule-based replies handle common keywords, LLM handles nuanced queries.

## Key Features

### ✅ Business-Scoped Responses Only
- LLM **only** uses business profile JSON data
- Strict system prompt prevents hallucinations
- No general knowledge or external information
- Safe, policy-compliant replies

### ✅ Smart Routing Logic
```
Customer Message
    ↓
Keyword Detected? (price, area, hours, book)
    ↓ YES → Rule-based reply (fast, deterministic)
    ↓ NO  → LLM reply (natural, context-aware)
    ↓ Fallback → Generic greeting
```

### ✅ Escalation Detection
- LLM detects when it cannot help
- Escalation phrases trigger human handoff
- Example: "I don't have that information"

### ✅ Opt-Out Capability
- Businesses can disable LLM per profile
- Falls back to rule-based + generic replies
- Database flag: `llm_enabled`

## Configuration

### Environment Variables

Add to `.env`:
```env
# LLM Configuration
LLM_ENABLED=true
LLM_PROVIDER=openai
LLM_MODEL=gpt-4o-mini
LLM_MAX_TOKENS=500
LLM_TEMPERATURE=0.3

# OpenAI API Key (required if LLM enabled)
OPENAI_API_KEY=your-api-key-here
```

### Config File

Located at `config/services.php`:
```php
'llm' => [
    'enabled' => env('LLM_ENABLED', true),
    'provider' => env('LLM_PROVIDER', 'openai'),
    'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    'max_tokens' => env('LLM_MAX_TOKENS', 500),
    'temperature' => env('LLM_TEMPERATURE', 0.3),
],
```

## System Prompt Pattern

The LLM uses a **strict system prompt** to enforce business-scoped replies:

```
You are a customer support assistant for {business_name}.

STRICT RULES:
1. ONLY answer using the business profile data provided below
2. If information is not in the profile, politely say you don't have that information
3. NEVER make up prices, services, areas, or hours
4. NEVER answer questions outside the business scope (politics, news, general advice, etc.)
5. Keep replies concise and helpful (2-3 sentences max)
6. Use a friendly, professional tone
7. If you cannot help, offer to connect them with the business owner

BUSINESS PROFILE:
```json
{
  "business_name": "...",
  "services": [...],
  "areas": [...],
  "operating_hours": {...},
  "booking_method": "..."
}
```

Customer Intent: {intent}

Provide a natural, helpful reply using ONLY the profile data above.
```

## Usage Examples

### Example 1: Keyword Match (Rule-Based)
**Customer:** "harga?"  
**System:** Detects `PRICE` intent → Uses rule-based reply  
**Reply:**
```
💰 *Our Services & Prices:*
• House Cleaning: RM80
• Office Cleaning: RM120
```

### Example 2: Non-Keyword Question (LLM)
**Customer:** "Do you provide house cleaning services?"  
**System:** No keyword → Calls LLM with business profile  
**Reply (LLM):**
```
Yes, we provide house cleaning services starting at RM80. 
Would you like to schedule an appointment?
```

### Example 3: Out-of-Scope Question
**Customer:** "What is the weather today?"  
**LLM Response:**
```
I don't have that information in my business profile. 
Let me connect you with the owner for assistance.
```
**System:** Escalation detected → Sends escalation message  
**Reply:**
```
Thank you for your message! This requires a personal response. 
We'll get back to you shortly!
```

### Example 4: LLM Disabled
**Customer:** "Tell me about your company"  
**System:** LLM disabled → Falls back to generic  
**Reply:**
```
Hello! How can we help you today?

You can ask about:
• Our services and prices
• Areas we cover
• Operating hours
• How to book
```

## Technical Implementation

### Services

#### LLMService
```php
$llmService->generateReply(
    $business,      // Business model with profile JSON
    $message,       // Customer message
    $intent         // Optional detected intent
);

// Returns:
[
    'success' => true/false,
    'reply' => 'Natural language reply',
    'escalation_needed' => true/false,
    'tokens_used' => 50,
]
```

#### AutoReplyService (Updated)
```php
public function generateReply(string $message, Business $business): ?string
{
    // 1. Check after-hours
    if ($this->isAfterHours($business)) {
        return $this->generateAfterHoursReply($business);
    }

    // 2. Detect keywords
    $detectedIntents = $this->detectIntents($message);

    // 3. If keyword match → Rule-based
    if (!empty($detectedIntents)) {
        return $this->bundleReplies($detectedIntents, $business);
    }

    // 4. If LLM enabled → LLM reply
    if ($business->llm_enabled && $this->llmService->isAvailable()) {
        $llmResult = $this->llmService->generateReply($business, $message);
        
        if ($llmResult['success'] && !$llmResult['escalation_needed']) {
            return $llmResult['reply'];
        }
        
        if ($llmResult['escalation_needed']) {
            return "Thank you for your message! This requires a personal response...";
        }
    }

    // 5. Fallback → Generic greeting
    return "Hello! How can we help you today?...";
}
```

### Database Schema

**businesses** table (added column):
```sql
ALTER TABLE businesses ADD COLUMN llm_enabled BOOLEAN DEFAULT TRUE;
```

### Escalation Detection

The system detects escalation from LLM responses containing specific phrases:
- "need to check with the owner"
- "check with the owner"
- "don't have that specific information"
- "contact the owner directly"
- "reach out to the owner"
- "i cannot help with that"
- "i can't help with that"
- "unable to help with that"
- "outside my knowledge"

**Note:** Phrases are more specific now to avoid false positives (e.g., "not available" in area descriptions)

## Safety & Security

### No Hallucination Risk
- ✅ Strict system prompt enforces business-only data
- ✅ JSON profile explicitly provided in each call
- ✅ Temperature set low (0.3) for consistency
- ✅ Escalation detection for out-of-scope queries

### Data Privacy
- ✅ Only current customer message sent to LLM
- ✅ Business profile already public (via onboarding)
- ✅ No customer history or sensitive data
- ✅ Opt-out available per business

### Cost Control
- ✅ Keyword matching reduces LLM calls
- ✅ Max token limit (500) enforced
- ✅ Rule-based replies remain primary
- ✅ LLM only for nuanced queries

## Testing

### Run LLM Tests
```bash
php artisan test --filter=LLMIntegrationTest
```

### Test Coverage
- ✅ Keyword match uses rules (not LLM)
- ✅ Non-keyword uses LLM when enabled
- ✅ LLM refuses out-of-scope questions
- ✅ LLM disabled falls back to generic
- ✅ Business opt-out works correctly
- ✅ System prompt built correctly
- ✅ API errors handled gracefully
- ✅ Escalation messages shown

## Monitoring & Logs

### LLM Calls Logged
```php
Log::info('LLM reply generated', [
    'business' => $business->name,
    'intent' => $intent,
    'tokens' => $tokens_used,
    'escalation' => $escalation_needed,
]);
```

### Routing Decisions Logged
```php
Log::info('Using rule-based reply', ['intents' => $detectedIntents]);
Log::info('No keyword match, trying LLM', ['message' => $message]);
Log::info('Using fallback generic reply');
```

### Error Logging
```php
Log::error('LLM generation failed', [
    'error' => $e->getMessage(),
    'business' => $business->name,
]);
```

## Deliverables ✅

- [x] Natural, context-aware replies using business profile
- [x] No hallucination (strict business-scoped prompts)
- [x] Policy-safe (escalation for complex/sensitive queries)
- [x] Keyword routing (rules take priority)
- [x] Opt-out capability per business
- [x] Graceful fallbacks (LLM error → generic reply)
- [x] Comprehensive test coverage (8 tests)

## Files Created/Modified

### New Files
- `app/Services/LLMService.php` - LLM integration with strict prompting
- `database/factories/BusinessFactory.php` - Factory for testing
- `database/migrations/2026_01_10_142355_add_llm_enabled_to_businesses_table.php`
- `tests/Feature/LLMIntegrationTest.php` - Comprehensive LLM tests
- `LLM_INTEGRATION.md` - This documentation

### Modified Files
- `app/Models/Business.php` - Added `llm_enabled` column, `HasFactory` trait
- `app/Services/AutoReplyService.php` - Integrated LLM routing logic
- `config/services.php` - Added LLM configuration
- `composer.json` - Added `openai-php/laravel` package

## Cost Estimation

Using `gpt-4o-mini` (recommended):
- Input: ~300 tokens per call (system prompt + business profile)
- Output: ~100-150 tokens per reply
- Total: ~400-450 tokens per call
- Cost: ~$0.0002 per reply (at current pricing)

For 1000 customer messages:
- Keyword matches: ~600 (no LLM cost)
- LLM calls: ~400
- Total cost: ~$0.08

## Best Practices

1. **Keep business profiles updated** - LLM quality depends on profile data
2. **Monitor escalation rate** - High rate indicates missing profile data
3. **Review LLM logs** - Check for unexpected responses
4. **Test with real messages** - Use actual customer queries for validation
5. **Set appropriate token limits** - Balance cost vs reply quality
6. **Use low temperature** - Ensures consistent, deterministic replies

## Troubleshooting

### Issue: LLM not being called
**Check:**
- `LLM_ENABLED=true` in `.env`
- `OPENAI_API_KEY` is set
- `llm_enabled=true` for the business
- Message doesn't match keywords

### Issue: Generic replies instead of LLM
**Check:**
- LLM service availability: `$llmService->isAvailable()`
- OpenAI API key valid
- No API errors in logs

### Issue: LLM makes up information
**Check:**
- System prompt includes "STRICT RULES"
- Business profile JSON is complete
- Temperature is low (0.3)
- Review and report for prompt tuning
