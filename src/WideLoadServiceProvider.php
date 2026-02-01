<?php

namespace Cosmastech\LaravelWideLoad;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Log\Context\Events\ContextDehydrating;
use Illuminate\Log\Context\Events\ContextHydrated;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WideLoadServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wide-load.php', 'wide-load');

        $this->app->scoped(WideLoad::class, function (Application $app): WideLoad {
            $config = Container::getInstance()->make('config');
            /** @var \Illuminate\Config\Repository $config */
            $config = $app->make('config');

            /** @var bool $enabled */
            $enabled = $config->get('wide-load.enabled', true);

            /** @var string $logLevel */
            $logLevel = $config->get('wide-load.log_level', 'info');

            return new WideLoad($enabled, $logLevel);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/wide-load.php' => $this->app->configPath('wide-load.php'),
        ], 'wide-load-config');

        $this->registerMacros();
        $this->registerEventListeners();
    }

    protected function registerMacros(): void
    {
        ContextRepository::macro('wideLoad', function (): WideLoad {
            return app(WideLoad::class);
        });

        ContextRepository::macro('addWide', function (string|array $key, mixed $value = null): WideLoad {
            /** @var string|array<string, mixed> $key */
            return app(WideLoad::class)->add($key, $value);
        });

        ContextRepository::macro('reportWide', function (): void {
            app(WideLoad::class)->report();
        });
    }

    protected function registerEventListeners(): void
    {
        $reportAndFlush = function (): void {
            /** @var WideLoad $wideLoad */
            $wideLoad = $this->app->make(WideLoad::class);
            $wideLoad->report();
            $wideLoad->flush();
        };

        Event::listen(Terminating::class, $reportAndFlush);
        Event::listen(JobProcessed::class, $reportAndFlush);
        Event::listen(JobFailed::class, $reportAndFlush);

        if ($this->app->make('config')->boolean('wide-load.serializable', true)) { // @phpstan-ignore method.nonObject
            $this->registerSerializationListeners();
        }
    }

    protected function registerSerializationListeners(): void
    {
        Event::listen(ContextDehydrating::class, function (ContextDehydrating $event): void {
            /** @var WideLoad $wideLoad */
            $wideLoad = $this->app->make(WideLoad::class);
            $data = $wideLoad->all();

            if ($data !== []) {
                $event->context->addHidden(WideLoad::CONTEXT_KEY, $data);
            }
        });

        Event::listen(ContextHydrated::class, function (ContextHydrated $event): void {
            /** @var array<string, mixed>|null $data */
            $data = $event->context->getHidden(WideLoad::CONTEXT_KEY);

            if ($data !== null) {
                /** @var WideLoad $wideLoad */
                $wideLoad = $this->app->make(WideLoad::class);
                $wideLoad->add($data);

                $event->context->forgetHidden(WideLoad::CONTEXT_KEY);
            }
        });
    }
}
