<?php

namespace Cosmastech\WideLoad;

use Illuminate\Container\Container;

class WideLoadReporter
{
    public static function reportAndFlush(): void
    {
        $container = Container::getInstance();

        if (! $container->make('config')->boolean('wide-load.auto_report', true)) { // @phpstan-ignore method.nonObject
            return;
        }

        $container->make(WideLoad::class)->report()->flush();
    }
}
