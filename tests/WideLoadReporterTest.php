<?php

namespace Cosmastech\WideLoad\Tests;

use Cosmastech\WideLoad\Facades\WideLoad as WideLoadFacade;
use Cosmastech\WideLoad\WideLoadReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WideLoadReporter::class)]
final class WideLoadReporterTest extends TestCase
{
    #[Test]
    public function autoReportEnabled_reportAndFlush_reportsAndFlushes(): void
    {
        // Given auto_report is enabled on the instance
        WideLoadFacade::enableAutoReporting(true);

        // And we have bound some data to WideLoad
        WideLoadFacade::add('key', 'value');

        // When we report and flush
        WideLoadReporter::reportAndFlush();

        // Then we recorded a log
        self::assertTrue(
            $this->logHandler->hasInfo(['message' => 'Request completed.', 'context' => ['key' => 'value']])
        );
        // And the WideLoad has been cleared
        self::assertSame([], WideLoadFacade::all());
    }

    #[Test]
    public function autoReportDisabled_reportAndFlush_doesNotReport(): void
    {
        // Given we have bound some data to WideLoad
        WideLoadFacade::add('key', 'value');

        // And auto_report is disabled on the instance
        WideLoadFacade::enableAutoReporting(false);

        // When we report and flush
        WideLoadReporter::reportAndFlush();

        // Then no log was recorded
        self::assertFalse($this->logHandler->hasInfoRecords());
        // And the WideLoad data remains intact
        self::assertSame(['key' => 'value'], WideLoadFacade::all());
    }
}
