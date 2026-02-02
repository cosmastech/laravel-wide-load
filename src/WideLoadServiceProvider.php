<?php

namespace Cosmastech\WideLoad;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Container\Container;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Log\Context\Events\ContextDehydrating;
use Illuminate\Log\Context\Events\ContextHydrated;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

class WideLoadServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/wide-load.php', 'wide-load');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/wide-load.php' => $this->app->configPath('wide-load.php'),
        ], 'wide-load-config');

        $this->registerMacros();
        $this->registerEventListeners();
    }

    protected function registerMacros(): void
    {
        ContextRepository::macro('wideLoad', function (): WideLoad {
            return Container::getInstance()->make(WideLoad::class);
        });

        ContextRepository::macro('addWide', function (string|array $key, mixed $value = null): WideLoad {
            /** @var string|array<string, mixed> $key */
            return Container::getInstance()->make(WideLoad::class)->add($key, $value);
        });

        ContextRepository::macro('reportWide', function (): void {
            Container::getInstance()->make(WideLoad::class)->report();
        });
    }

    protected function registerEventListeners(): void
    {
        $reportAndFlush = static function (): void {
            /** @var WideLoad $wideLoad */
            $wideLoad = Container::getInstance()->make(WideLoad::class);
            $wideLoad->report();
            $wideLoad->flush();
        };

        Event::listen(Terminating::class, $reportAndFlush);
        Event::listen(CommandFinished::class, $reportAndFlush);
        Event::listen(JobProcessed::class, $reportAndFlush);
        Event::listen(JobFailed::class, $reportAndFlush);

        if ($this->app->make('config')->boolean('wide-load.serializable', true)) { // @phpstan-ignore method.nonObject
            $this->registerSerializationListeners();
        }
    }

    protected function registerSerializationListeners(): void
    {
        Event::listen(
            ContextDehydrating::class,
            static function (ContextDehydrating $event): void {
                /** @var WideLoad $wideLoad */
                $wideLoad = Container::getInstance()->make(WideLoad::class);
                $data = $wideLoad->all();

                if ($data !== []) {
                    $event->context->addHidden(WideLoad::CONTEXT_KEY, $data);
                }
            }
        );

        Event::listen(
            ContextHydrated::class,
            static function (ContextHydrated $event): void {
                /** @var array<string, mixed>|null $data */
                $data = $event->context->getHidden(WideLoad::CONTEXT_KEY);

                if ($data !== null) {
                    /** @var WideLoad $wideLoad */
                    $wideLoad = Container::getInstance()->make(WideLoad::class);
                    $wideLoad->add($data);

                    $event->context->forgetHidden(WideLoad::CONTEXT_KEY);
                }
            }
        );
    }
}
