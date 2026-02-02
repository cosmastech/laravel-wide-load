<?php

namespace Cosmastech\WideLoad\Events;

/**
 * Dispatched when WideLoad is reporting data.
 *
 * The $data property contains the collected key-value pairs at the time of reporting.
 */
class WideLoadReported
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly array $data,
    ) {
    }
}
