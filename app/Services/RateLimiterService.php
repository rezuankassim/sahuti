<?php

namespace App\Services;

use App\Models\AutoReplyLog;
use Illuminate\Support\Facades\Cache;

class RateLimiterService
{
    /**
     * Check if customer is in burst mode (sending messages too quickly)
     * This prevents replying to every message in a rapid burst, but allows normal conversation
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

        // Short burst window (5 seconds) to detect rapid messages
        $burstWindowSeconds = config('auto_reply.burst_window_seconds', 5);
        $secondsSinceLastReply = now()->diffInSeconds($lastReplyTime);

        return $secondsSinceLastReply < $burstWindowSeconds;
    }

    /**
     * Set burst detection cooldown for customer
     * This is a short window to detect if customer is sending multiple messages quickly
     */
    public function setCooldown(string $customerPhone): void
    {
        $cacheKey = "auto_reply_cooldown:{$customerPhone}";
        $burstWindowSeconds = config('auto_reply.burst_window_seconds', 5);

        Cache::put($cacheKey, true, $burstWindowSeconds);
    }

    /**
     * Get remaining burst window time in seconds
     */
    public function getRemainingCooldown(string $customerPhone): int
    {
        $lastReplyTime = AutoReplyLog::getLastReplyTime($customerPhone);

        if (! $lastReplyTime) {
            return 0;
        }

        $burstWindowSeconds = config('auto_reply.burst_window_seconds', 5);
        $secondsSinceLastReply = now()->diffInSeconds($lastReplyTime);

        return max(0, $burstWindowSeconds - $secondsSinceLastReply);
    }
}
