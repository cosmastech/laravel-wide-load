<?php

namespace Cosmastech\WideLoad\Events;

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
