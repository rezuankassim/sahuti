<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Reply Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for auto-reply safety, limits, and behavior
    |
    */

    // Burst detection window: prevents replying to every message if customer
    // sends multiple messages within this window. After this window, normal
    // conversation flow resumes. Default: 5 seconds
    'burst_window_seconds' => (int) env('AUTO_REPLY_BURST_WINDOW_SECONDS', 5),

    // Legacy cooldown config (deprecated, use burst_window_seconds instead)
    'cooldown_seconds' => (int) env('AUTO_REPLY_COOLDOWN_SECONDS', 5),

    'llm_failure_threshold' => (float) env('LLM_FAILURE_THRESHOLD', 0.5),

    'llm_failure_window_minutes' => (int) env('LLM_FAILURE_WINDOW_MINUTES', 60),

];
