<?php

namespace Cosmastech\WideLoad\Tests;

use Monolog\Handler\TestHandler;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected TestHandler $logHandler;

    #[Override]
    protected function defineEnvironment($app): void
    {
        $app['config']->set('logging.default', 'test');
        $app['config']->set('logging.channels.test', [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Illuminate\Log\LogManager $log */
        $log = $this->app->make('log');

        /** @var \Monolog\Logger $logger */
        $logger = $log->driver('test')->getLogger();

        $handler = $logger->getHandlers()[0];
        assert($handler instanceof TestHandler);

        $this->logHandler = $handler;
    }
}
