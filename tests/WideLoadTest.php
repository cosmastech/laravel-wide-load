<?php

namespace Cosmastech\LaravelWideLoad\Tests;

use Cosmastech\LaravelWideLoad\WideLoad;
use Illuminate\Support\Facades\Log;

class WideLoadTest extends TestCase
{
    private WideLoad $wideLoad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wideLoad = $this->app->make(WideLoad::class);
    }

    public function test_add_and_get(): void
    {
        $this->wideLoad->add('user_id', 42);

        $this->assertSame(42, $this->wideLoad->get('user_id'));
    }

    public function test_add_array(): void
    {
        $this->wideLoad->add(['user_id' => 42, 'role' => 'admin']);

        $this->assertSame(42, $this->wideLoad->get('user_id'));
        $this->assertSame('admin', $this->wideLoad->get('role'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', $this->wideLoad->get('missing', 'fallback'));
    }

    public function test_add_if_does_not_overwrite(): void
    {
        $this->wideLoad->add('key', 'first');
        $this->wideLoad->addIf('key', 'second');

        $this->assertSame('first', $this->wideLoad->get('key'));
    }

    public function test_add_if_adds_when_missing(): void
    {
        $this->wideLoad->addIf('key', 'value');

        $this->assertSame('value', $this->wideLoad->get('key'));
    }

    public function test_has(): void
    {
        $this->assertFalse($this->wideLoad->has('key'));

        $this->wideLoad->add('key', 'value');

        $this->assertTrue($this->wideLoad->has('key'));
    }

    public function test_all(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $this->wideLoad->all());
    }

    public function test_only(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $this->wideLoad->only(['a', 'c']));
    }

    public function test_except(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $this->wideLoad->except(['b']));
    }

    public function test_forget_single_key(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);
        $this->wideLoad->forget('a');

        $this->assertFalse($this->wideLoad->has('a'));
        $this->assertTrue($this->wideLoad->has('b'));
    }

    public function test_forget_multiple_keys(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->wideLoad->forget(['a', 'c']);

        $this->assertSame(['b' => 2], $this->wideLoad->all());
    }

    public function test_pull(): void
    {
        $this->wideLoad->add('key', 'value');

        $this->assertSame('value', $this->wideLoad->pull('key'));
        $this->assertFalse($this->wideLoad->has('key'));
    }

    public function test_pull_returns_default_when_missing(): void
    {
        $this->assertSame('default', $this->wideLoad->pull('missing', 'default'));
    }

    public function test_flush(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);
        $this->wideLoad->flush();

        $this->assertSame([], $this->wideLoad->all());
    }

    public function test_push(): void
    {
        $this->wideLoad->push('tags', 'first');
        $this->wideLoad->push('tags', 'second', 'third');

        $this->assertSame(['first', 'second', 'third'], $this->wideLoad->get('tags'));
    }

    public function test_increment(): void
    {
        $this->wideLoad->increment('count');
        $this->wideLoad->increment('count');
        $this->wideLoad->increment('count', 3);

        $this->assertSame(5, $this->wideLoad->get('count'));
    }

    public function test_decrement(): void
    {
        $this->wideLoad->increment('count', 10);
        $this->wideLoad->decrement('count', 3);

        $this->assertSame(7, $this->wideLoad->get('count'));
    }

    public function test_report_logs_wide_event(): void
    {
        Log::spy();

        $this->wideLoad->add('user_id', 42);
        $this->wideLoad->report();

        Log::shouldHaveReceived('log')
            ->once()
            ->with('info', 'Wide event.', ['user_id' => 42]);
    }

    public function test_report_uses_configured_log_level(): void
    {
        Log::spy();

        $this->app['config']->set('wide-load.log_level', 'debug');

        /** @var WideLoad $wideLoad */
        $wideLoad = new WideLoad(true, 'debug');

        $wideLoad->add('key', 'value');
        $wideLoad->report();

        Log::shouldHaveReceived('log')
            ->once()
            ->with('debug', 'Wide event.', ['key' => 'value']);
    }

    public function test_report_using_custom_callback(): void
    {
        $reported = [];

        $this->wideLoad->reportUsing(function (array $data) use (&$reported): void {
            $reported = $data;
        });

        $this->wideLoad->add('key', 'value');
        $this->wideLoad->report();

        $this->assertSame(['key' => 'value'], $reported);
    }

    public function test_report_does_nothing_when_disabled(): void
    {
        Log::spy();

        $this->wideLoad->disable();
        $this->wideLoad->add('key', 'value');
        $this->wideLoad->report();

        Log::shouldNotHaveReceived('log');
    }

    public function test_enable_and_disable(): void
    {
        $this->assertTrue($this->wideLoad->enabled());

        $this->wideLoad->disable();
        $this->assertFalse($this->wideLoad->enabled());

        $this->wideLoad->enable();
        $this->assertTrue($this->wideLoad->enabled());
    }

    public function test_fluent_api(): void
    {
        $result = $this->wideLoad
            ->add('a', 1)
            ->addIf('b', 2)
            ->push('tags', 'x')
            ->increment('count');

        $this->assertInstanceOf(WideLoad::class, $result);
        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'tags' => ['x'],
            'count' => 1,
        ], $this->wideLoad->all());
    }
}
