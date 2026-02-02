<?php

namespace Cosmastech\WideLoad\Tests;

use Cosmastech\WideLoad\WideLoad;
use Cosmastech\WideLoad\WideLoadConfig;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WideLoad::class)]
final class WideLoadTest extends TestCase
{
    private WideLoad $wideLoad;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wideLoad = $this->app->make(WideLoad::class);
    }

    #[Test]
    public function singleValue_add_storesValue(): void
    {
        $this->wideLoad->add('user_id', 42);

        $this->assertSame(42, $this->wideLoad->get('user_id'));
    }

    #[Test]
    public function arrayOfValues_add_storesAllValues(): void
    {
        $this->wideLoad->add(['user_id' => 42, 'role' => 'admin']);

        $this->assertSame(42, $this->wideLoad->get('user_id'));
        $this->assertSame('admin', $this->wideLoad->get('role'));
    }

    #[Test]
    public function missingKey_get_returnsDefault(): void
    {
        $this->assertSame('fallback', $this->wideLoad->get('missing', 'fallback'));
    }

    #[Test]
    public function keyAlreadyExists_addIf_doesNotOverwrite(): void
    {
        $this->wideLoad->add('key', 'first');
        $this->wideLoad->addIf('key', 'second');

        $this->assertSame('first', $this->wideLoad->get('key'));
    }

    #[Test]
    public function keyDoesNotExist_addIf_addsValue(): void
    {
        $this->wideLoad->addIf('key', 'value');

        $this->assertSame('value', $this->wideLoad->get('key'));
    }

    #[Test]
    public function keyMissing_has_returnsFalse(): void
    {
        $this->assertFalse($this->wideLoad->has('key'));
    }

    #[Test]
    public function keyPresent_has_returnsTrue(): void
    {
        $this->wideLoad->add('key', 'value');

        $this->assertTrue($this->wideLoad->has('key'));
    }

    #[Test]
    public function multipleValuesAdded_all_returnsEntireArray(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $this->wideLoad->all());
    }

    #[Test]
    public function subsetOfKeys_only_returnsMatchingKeys(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $this->wideLoad->only(['a', 'c']));
    }

    #[Test]
    public function subsetOfKeys_except_returnsRemainingKeys(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $this->wideLoad->except(['b']));
    }

    #[Test]
    public function singleKey_forget_removesOnlyThatKey(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);
        $this->wideLoad->forget('a');

        $this->assertFalse($this->wideLoad->has('a'));
        $this->assertTrue($this->wideLoad->has('b'));
    }

    #[Test]
    public function multipleKeys_forget_removesAllSpecifiedKeys(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->wideLoad->forget(['a', 'c']);

        $this->assertSame(['b' => 2], $this->wideLoad->all());
    }

    #[Test]
    public function existingKey_pull_returnsValueAndRemovesIt(): void
    {
        $this->wideLoad->add('key', 'value');

        $this->assertSame('value', $this->wideLoad->pull('key'));
        $this->assertFalse($this->wideLoad->has('key'));
    }

    #[Test]
    public function missingKey_pull_returnsDefault(): void
    {
        $this->assertSame('default', $this->wideLoad->pull('missing', 'default'));
    }

    #[Test]
    public function dataPresent_flush_removesAllData(): void
    {
        $this->wideLoad->add(['a' => 1, 'b' => 2]);
        $this->wideLoad->flush();

        $this->assertSame([], $this->wideLoad->all());
    }

    #[Test]
    public function multipleCalls_increment_sumsCorrectly(): void
    {
        $this->wideLoad->increment('count');
        $this->wideLoad->increment('count');
        $this->wideLoad->increment('count', 3);

        $this->assertSame(5, $this->wideLoad->get('count'));
    }

    #[Test]
    public function afterIncrement_decrement_subtractsCorrectly(): void
    {
        $this->wideLoad->increment('count', 10);
        $this->wideLoad->decrement('count', 3);

        $this->assertSame(7, $this->wideLoad->get('count'));
    }

    #[Test]
    public function emptyData_report_doesNotLog(): void
    {
        $this->wideLoad->report();

        $this->assertFalse($this->logHandler->hasInfoRecords());
    }

    #[Test]
    public function dataPresent_report_logsWideEvent(): void
    {
        $this->wideLoad->add('user_id', 42);
        $this->wideLoad->report();

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['user_id' => 42]])
        );
    }

    #[Test]
    public function debugLogLevel_report_usesConfiguredLevel(): void
    {
        $wideLoad = new WideLoad(new WideLoadConfig(logLevel: 'debug'));

        $wideLoad->add('key', 'value');
        $wideLoad->report();

        $this->assertTrue(
            $this->logHandler->hasDebug(['message' => 'Request completed.', 'context' => ['key' => 'value']])
        );
    }

    #[Test]
    public function customLogMessage_report_usesConfiguredMessage(): void
    {
        $wideLoad = new WideLoad(new WideLoadConfig(logMessage: 'Lifecycle finished.'));

        $wideLoad->add('key', 'value');
        $wideLoad->report();

        $this->assertTrue(
            $this->logHandler->hasInfo(['message' => 'Lifecycle finished.', 'context' => ['key' => 'value']])
        );
    }

    #[Test]
    public function customCallbackSet_report_usesCallback(): void
    {
        $reported = [];

        $this->wideLoad->reportUsing(function (array $data) use (&$reported): void {
            $reported = $data;
        });

        $this->wideLoad->add('key', 'value');
        $this->wideLoad->report();

        $this->assertSame(['key' => 'value'], $reported);
    }

    #[Test]
    public function chainedCalls_fluentApi_returnsWideLoadInstance(): void
    {
        $result = $this->wideLoad
            ->add('a', 1)
            ->addIf('b', 2)
            ->increment('count');

        $this->assertInstanceOf(WideLoad::class, $result);
        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'count' => 1,
        ], $this->wideLoad->all());
    }
}
