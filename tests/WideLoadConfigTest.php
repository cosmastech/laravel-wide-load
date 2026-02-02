<?php

namespace Cosmastech\WideLoad\Tests;

use Cosmastech\WideLoad\WideLoadConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WideLoadConfig::class)]
final class WideLoadConfigTest extends TestCase
{
    #[Test]
    public function resolvedTwice_make_returnsSameInstance(): void
    {
        $first = $this->app->make(WideLoadConfig::class);
        $second = $this->app->make(WideLoadConfig::class);

        $this->assertSame($first, $second);
    }

    #[Test]
    public function defaultConfig_logLevel_isInfo(): void
    {
        $config = $this->app->make(WideLoadConfig::class);

        $this->assertSame('info', $config->logLevel);
    }

    #[Test]
    public function customConfig_logLevel_readsFromConfig(): void
    {
        $this->app['config']->set('wide-load.log_level', 'debug');
        $this->app->forgetScopedInstances();

        $config = $this->app->make(WideLoadConfig::class);

        $this->assertSame('debug', $config->logLevel);
    }

    #[Test]
    public function defaultConfig_logMessage_isRequestCompleted(): void
    {
        $config = $this->app->make(WideLoadConfig::class);

        $this->assertSame('Request completed.', $config->logMessage);
    }

    #[Test]
    public function customConfig_logMessage_readsFromConfig(): void
    {
        $this->app['config']->set('wide-load.log_message', 'Done.');
        $this->app->forgetScopedInstances();

        $config = $this->app->make(WideLoadConfig::class);

        $this->assertSame('Done.', $config->logMessage);
    }
}
