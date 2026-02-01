<?php

namespace Cosmastech\LaravelWideLoad;

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
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wide-load.php', 'wide-load');

        $this->app->singleton(WideLoad::class, function (\Illuminate\Contracts\Foundation\Application $app): WideLoad {
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

    private function registerMacros(): void
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

    private function registerEventListeners(): void
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

        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');

        /** @var bool $serializable */
        $serializable = $config->get('wide-load.serializable', true);

        if ($serializable) {
            $this->registerSerializationListeners();
        }
    }

    private function registerSerializationListeners(): void
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
