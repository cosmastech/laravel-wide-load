<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master toggle for wide event logging. When disabled, report() will
    | silently no-op.
    |
    */

    'enabled' => env('WIDE_LOAD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | The log level used by the default report callback.
    |
    */

    'log_level' => env('WIDE_LOAD_LOG_LEVEL', 'info'),

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

    'serializable' => env('WIDE_LOAD_SERIALIZABLE', true),

];
