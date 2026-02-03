<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto Report
    |--------------------------------------------------------------------------
    |
    | Toggle for automatic wide event reporting. When disabled, lifecycle
    | events will not trigger automatic report and flush. Manual calls
    | to report() will still work.
    |
    */
    'auto_report' => (bool) env('WIDE_LOAD_AUTO_REPORT', true),

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | The log level used by the default report callback.
    |
    */
    'log_level' => (string) env('WIDE_LOAD_LOG_LEVEL', 'info'),

    /*
    |--------------------------------------------------------------------------
    | Log Message
    |--------------------------------------------------------------------------
    |
    | The message used by the default report callback.
    |
    */
    'log_message' => (string) env('WIDE_LOAD_LOG_MESSAGE', 'Request completed.'),

    /*
    |--------------------------------------------------------------------------
    | Serializable
    |--------------------------------------------------------------------------
    |
    | When enabled, wide load data will be serialized across queue jobs via
    | Laravel's Context dehydrating/hydrated events. Set to false if you
    | don't want wide load data to carry over from the dispatching process
    | into queued jobs.
    |
    */
    'serializable' => (bool) env('WIDE_LOAD_SERIALIZABLE', true),
];
