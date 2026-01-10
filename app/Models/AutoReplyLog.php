<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoReplyLog extends Model
{
    protected $fillable = [
        'customer_phone',
        'business_id',
        'message_text',
        'reply_text',
        'reply_type',
        'llm_tokens_used',
        'rate_limited',
        'duration_ms',
    ];

    protected $casts = [
        'rate_limited' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get last reply timestamp for a customer
     */
    public static function getLastReplyTime(string $customerPhone): ?\Carbon\Carbon
    {
        $log = self::where('customer_phone', $customerPhone)
            ->where('rate_limited', false)
            ->latest()
            ->first();

        return $log?->created_at;
    }
}
