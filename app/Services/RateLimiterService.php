<?php

namespace App\Services;

use App\Models\AutoReplyLog;
use Illuminate\Support\Facades\Cache;

class RateLimiterService
{
    /**
     * Check if customer is rate limited (90 second cooldown)
     */
    public function isRateLimited(string $customerPhone): bool
    {
        $cacheKey = "auto_reply_cooldown:{$customerPhone}";

        // Check cache first for performance
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Fallback to database if cache miss
        $lastReplyTime = AutoReplyLog::getLastReplyTime($customerPhone);

        if (! $lastReplyTime) {
            return false;
        }

        $cooldownSeconds = config('auto_reply.cooldown_seconds', 90);
        $secondsSinceLastReply = now()->diffInSeconds($lastReplyTime);

        return $secondsSinceLastReply < $cooldownSeconds;
    }

    /**
     * Set rate limit cooldown for customer
     */
    public function setCooldown(string $customerPhone): void
    {
        $cacheKey = "auto_reply_cooldown:{$customerPhone}";
        $cooldownSeconds = config('auto_reply.cooldown_seconds', 90);

        Cache::put($cacheKey, true, $cooldownSeconds);
    }

    /**
     * Get remaining cooldown time in seconds
     */
    public function getRemainingCooldown(string $customerPhone): int
    {
        $lastReplyTime = AutoReplyLog::getLastReplyTime($customerPhone);

        if (! $lastReplyTime) {
            return 0;
        }

        $cooldownSeconds = config('auto_reply.cooldown_seconds', 90);
        $secondsSinceLastReply = now()->diffInSeconds($lastReplyTime);

        return max(0, $cooldownSeconds - $secondsSinceLastReply);
    }
}
