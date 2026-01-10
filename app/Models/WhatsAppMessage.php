<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'message_id',
        'direction',
        'from',
        'to',
        'message_type',
        'content',
        'status',
        'metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
    ];

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
