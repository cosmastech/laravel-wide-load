<?php

namespace Cosmastech\LaravelWideLoad\Tests;

use Cosmastech\LaravelWideLoad\WideLoad;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Log\Context\Events\ContextDehydrating;
use Illuminate\Log\Context\Events\ContextHydrated;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class WideLoadServiceProviderTest extends TestCase
{
    public function test_wide_load_is_registered_as_singleton(): void
    {
        $first = $this->app->make(WideLoad::class);
        $second = $this->app->make(WideLoad::class);

        $this->assertSame($first, $second);
    }

    public function test_config_is_merged(): void
    {
        $this->assertTrue($this->app['config']->get('wide-load.enabled'));
        $this->assertSame('info', $this->app['config']->get('wide-load.log_level'));
    }

    public function test_wide_load_macro_is_registered(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('wideLoad'));

        $result = Context::wideLoad();

        $this->assertInstanceOf(WideLoad::class, $result);
    }

    public function test_add_wide_macro_is_registered(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('addWide'));

        Context::addWide('macro_key', 'macro_value');

        $wideLoad = $this->app->make(WideLoad::class);
        $this->assertSame('macro_value', $wideLoad->get('macro_key'));
    }

    public function test_report_wide_macro_is_registered(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('reportWide'));

        Log::spy();

        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        Context::reportWide();

        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Wide event.', ['key' => 'value']);
    }

    public function test_terminating_event_triggers_report_and_flush(): void
    {
        Log::spy();

        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('request_id', 'abc-123');

        Event::dispatch(new Terminating);

        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Wide event.', ['request_id' => 'abc-123']);

        $this->assertSame([], $wideLoad->all());
    }

    public function test_job_processed_event_triggers_report_and_flush(): void
    {
        Log::spy();

        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('job', 'SendEmail');

        $job = $this->createMock(Job::class);
        Event::dispatch(new JobProcessed('default', $job));

        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Wide event.', ['job' => 'SendEmail']);

        $this->assertSame([], $wideLoad->all());
    }

    public function test_job_failed_event_triggers_report_and_flush(): void
    {
        Log::spy();

        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('job', 'FailedJob');

        $job = $this->createMock(Job::class);
        Event::dispatch(new JobFailed('default', $job, new \Exception('Test failure')));

        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Wide event.', ['job' => 'FailedJob']);

        $this->assertSame([], $wideLoad->all());
    }

    public function test_disabled_via_config(): void
    {
        $this->app['config']->set('wide-load.enabled', false);

        // Re-create the singleton with updated config
        $this->app->forgetInstance(WideLoad::class);

        Log::spy();

        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        Event::dispatch(new Terminating);

        Log::shouldNotHaveReceived('log');
    }

    public function test_serializable_is_true_by_default(): void
    {
        $this->assertTrue($this->app['config']->get('wide-load.serializable'));
    }

    public function test_dehydrating_pushes_data_to_hidden_context_when_serializable(): void
    {
        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);

        Event::dispatch(new ContextDehydrating($context));

        $this->assertSame(['key' => 'value'], $context->getHidden(WideLoad::CONTEXT_KEY));
    }

    public function test_hydrated_restores_data_from_hidden_context_when_serializable(): void
    {
        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);
        $context->addHidden(WideLoad::CONTEXT_KEY, ['key' => 'value']);

        Event::dispatch(new ContextHydrated($context));

        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $this->assertSame('value', $wideLoad->get('key'));

        // Hidden key should be cleaned up
        $this->assertNull($context->getHidden(WideLoad::CONTEXT_KEY));
    }

    public function test_dehydrating_does_not_push_empty_data(): void
    {
        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);

        Event::dispatch(new ContextDehydrating($context));

        $this->assertNull($context->getHidden(WideLoad::CONTEXT_KEY));
    }
}
