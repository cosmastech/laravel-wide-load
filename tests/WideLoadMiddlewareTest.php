<?php

namespace Cosmastech\WideLoad\Tests;

use Cosmastech\WideLoad\Http\Middleware\WideLoadMiddleware;
use Cosmastech\WideLoad\WideLoad;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WideLoadMiddleware::class)]
final class WideLoadMiddlewareTest extends TestCase
{
    #[Test]
    public function terminate_reportsAndFlushes(): void
    {
        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        $middleware = new WideLoadMiddleware($wideLoad);
        $middleware->terminate(new Request(), new Response());

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['key' => 'value']])
        );
        $this->assertSame([], $wideLoad->all());
    }

    #[Test]
    public function handle_passesRequestThrough(): void
    {
        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);

        $middleware = new WideLoadMiddleware($wideLoad);
        $response = $middleware->handle(new Request(), fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    #[Test]
    public function afterMiddlewareTerminate_terminatingEvent_doesNotDoubleReport(): void
    {
        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        $middleware = new WideLoadMiddleware($wideLoad);
        $middleware->terminate(new Request(), new Response());

        $this->logHandler->clear();

        Event::dispatch(new Terminating());

        $this->assertFalse($this->logHandler->hasInfoRecords());
    }
}
