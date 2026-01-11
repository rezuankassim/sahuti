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
        'duration_ms',
    ];


    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
