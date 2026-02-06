<?php

namespace Cosmastech\WideLoad;

use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class WideLoadConfig
{
    public function __construct(
        #[Config('wide-load.auto_report', false)]
        public bool $autoReport = false,
        #[Config('wide-load.log_level', 'info')]
        public string $logLevel = 'info',
        #[Config('wide-load.log_message', 'Request completed.')]
        public string $logMessage = 'Request completed.',
    ) {
    }
}
