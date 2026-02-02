<?php

namespace Cosmastech\WideLoad;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class WideLoadConfig
{
    public function __construct(
        #[Config('wide-load.log_level', 'info')]
        public string $logLevel = 'info',
    ) {
    }
}
