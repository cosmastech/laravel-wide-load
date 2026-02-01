<?php

namespace Cosmastech\WideLoad\Tests;

use Cosmastech\WideLoad\WideLoad;
use Cosmastech\WideLoad\WideLoadServiceProvider;
use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Log\Context\Events\ContextDehydrating;
use Illuminate\Log\Context\Events\ContextHydrated;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WideLoadServiceProvider::class)]
final class WideLoadServiceProviderTest extends TestCase
{
    #[Test]
    public function resolvedTwice_make_returnsSameInstance(): void
    {
        $first = $this->app->make(WideLoad::class);
        $second = $this->app->make(WideLoad::class);

        $this->assertSame($first, $second);
    }

    #[Test]
    public function defaultConfig_mergeConfig_hasExpectedDefaults(): void
    {
        $this->assertTrue($this->app['config']->get('wide-load.enabled'));
        $this->assertSame('info', $this->app['config']->get('wide-load.log_level'));
    }

    #[Test]
    public function booted_wideLoadMacro_returnsWideLoadInstance(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('wideLoad'));

        $result = Context::wideLoad();

        $this->assertInstanceOf(WideLoad::class, $result);
    }

    #[Test]
    public function booted_addWideMacro_delegatesToWideLoad(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('addWide'));

        Context::addWide('macro_key', 'macro_value');

        $wideLoad = $this->app->make(WideLoad::class);
        $this->assertSame('macro_value', $wideLoad->get('macro_key'));
    }

    #[Test]
    public function booted_reportWideMacro_triggersReport(): void
    {
        $this->assertTrue(ContextRepository::hasMacro('reportWide'));

        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        Context::reportWide();

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['key' => 'value']])
        );
    }

    #[Test]
    public function dataPresent_terminatingEvent_reportsAndFlushes(): void
    {
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('request_id', 'abc-123');

        Event::dispatch(new Terminating());

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['request_id' => 'abc-123']])
        );
        $this->assertSame([], $wideLoad->all());
    }

    #[Test]
    public function dataPresent_jobProcessedEvent_reportsAndFlushes(): void
    {
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('job', 'SendEmail');

        $job = $this->createMock(Job::class);
        Event::dispatch(new JobProcessed('default', $job));

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['job' => 'SendEmail']])
        );
        $this->assertSame([], $wideLoad->all());
    }

    #[Test]
    public function dataPresent_jobFailedEvent_reportsAndFlushes(): void
    {
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('job', 'FailedJob');

        $job = $this->createMock(Job::class);
        Event::dispatch(new JobFailed('default', $job, new Exception('Test failure')));

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['job' => 'FailedJob']])
        );
        $this->assertSame([], $wideLoad->all());
    }

    #[Test]
    public function disabledViaConfig_terminatingEvent_doesNotLog(): void
    {
        $this->app['config']->set('wide-load.enabled', false);
        $this->app->forgetInstance(WideLoad::class);

        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        Event::dispatch(new Terminating());

        $this->assertFalse($this->logHandler->hasInfoRecords());
    }

    #[Test]
    public function defaultConfig_serializable_isTrue(): void
    {
        $this->assertTrue($this->app['config']->get('wide-load.serializable'));
    }

    #[Test]
    public function dataPresent_dehydrating_pushesToHiddenContext(): void
    {
        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $wideLoad->add('key', 'value');

        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);

        Event::dispatch(new ContextDehydrating($context));

        $this->assertSame(['key' => 'value'], $context->getHidden(WideLoad::CONTEXT_KEY));
    }

    #[Test]
    public function hiddenDataPresent_hydrated_restoresDataToWideLoad(): void
    {
        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);
        $context->addHidden(WideLoad::CONTEXT_KEY, ['key' => 'value']);

        Event::dispatch(new ContextHydrated($context));

        /** @var WideLoad $wideLoad */
        $wideLoad = $this->app->make(WideLoad::class);
        $this->assertSame('value', $wideLoad->get('key'));

        $this->assertNull($context->getHidden(WideLoad::CONTEXT_KEY));
    }

    #[Test]
    public function noData_dehydrating_doesNotSetHiddenContext(): void
    {
        /** @var ContextRepository $context */
        $context = $this->app->make(ContextRepository::class);

        Event::dispatch(new ContextDehydrating($context));

        $this->assertNull($context->getHidden(WideLoad::CONTEXT_KEY));
    }
}
