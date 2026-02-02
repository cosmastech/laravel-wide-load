<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master toggle for automatic wide event reporting. When disabled,
    | events will not trigger automatic report and flush. Manual calls
    | to report() will still work.
    |
    */

    'enabled' => (bool) env('WIDE_LOAD_ENABLED', true),

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
