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

    'llm_failure_threshold' => (float) env('LLM_FAILURE_THRESHOLD', 0.5),

    'llm_failure_window_minutes' => (int) env('LLM_FAILURE_WINDOW_MINUTES', 60),

];
