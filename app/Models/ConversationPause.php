<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationPause extends Model
{
    protected $fillable = [
        'phone_number',
        'paused_until',
    ];

    protected $casts = [
        'paused_until' => 'datetime',
    ];

    /**
     * Check if conversation with this phone number is currently paused
     */
    public static function isPaused(string $phoneNumber): bool
    {
        return self::where('phone_number', $phoneNumber)
            ->where('paused_until', '>', now())
            ->exists();
    }

    /**
     * Pause conversation for 30 minutes
     */
    public static function pauseConversation(string $phoneNumber): void
    {
        self::updateOrCreate(
            ['phone_number' => $phoneNumber],
            ['paused_until' => now()->addMinutes(30)]
        );
    }

    /**
     * Clean up expired pauses (optional, for maintenance)
     */
    public static function cleanupExpired(): int
    {
        return self::where('paused_until', '<', now())->delete();
    }
}
