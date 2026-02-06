<?php

namespace Cosmastech\WideLoad;

use Illuminate\Container\Container;

class WideLoadReporter
{
    /**
     * Report and flush the WideLoad if auto_reporting is enabled.
     */
    public static function reportAndFlush(): void
    {
        $wideLoad = Container::getInstance()->get(WideLoad::class);

        if (! $wideLoad->isAutoReportingEnabled()) {
            return;
        }

        $wideLoad->report()->flush();
    }
}
