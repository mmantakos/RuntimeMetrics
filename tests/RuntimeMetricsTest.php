<?php

use mmantakos\RuntimeMetrics\RuntimeMetrics;
use PHPUnit\Framework\TestCase;

class RuntimeMetricsTest extends TestCase
{
    private RuntimeMetrics $metrics;

    protected function setUp(): void
    {
        $this->metrics = new RuntimeMetrics();
    }

    public function testCanStartAndStopTimer(): void
    {
        $this->metrics->start('test');
        usleep(100000); // 100ms
        $this->metrics->stop('test');

        $duration = $this->metrics->getDuration('test');
        $this->assertGreaterThan(0.09, $duration);
        $this->assertLessThan(0.2, $duration);
    }

    public function testMeasureMethodExecutesAndRecords(): void
    {
        $time = $this->metrics->measure(fn() => usleep(50000), 'measure');
        $this->assertGreaterThan(0.04, $time);
        $this->assertLessThan(0.1, $time);

        $timers = $this->metrics->getTimers();
        $this->assertContains('measure', $timers);
    }

    public function testMemoryUsageAndPeakMemoryUsage(): void
    {
        $this->metrics->start('memory');
        $data = range(1, 50000);
        $this->metrics->stop('memory');

        $used = $this->metrics->getMemoryUsage('memory');
        $peak = $this->metrics->getPeakMemoryUsage('memory');

        $this->assertIsString($used);
        $this->assertIsString($peak);
    }

    public function testFormatBytes(): void
    {
        $this->assertSame('0 B', $this->metrics->formatBytes(0));
        $this->assertSame('1 KB', $this->metrics->formatBytes(1024, 0));
    }

    public function testFormatSeconds(): void
    {
        $this->assertStringContainsString('ms', $this->metrics->formatSeconds(0.005));
        $this->assertStringContainsString('s', $this->metrics->formatSeconds(1.2));
    }

    public function testReportAndToJson(): void
    {
        $this->metrics->measure(fn() => usleep(10000), 'json');
        $report = $this->metrics->report('json');
        $json = $this->metrics->toJson();

        $this->assertStringContainsString('json', $report);
        $this->assertJson($json);
        $this->assertArrayHasKey('json', json_decode($json, true));
    }

    public function testResetClearsTimers(): void
    {
        $this->metrics->measure(fn() => usleep(10000), 'cleanup');
        $this->assertCount(1, $this->metrics->getTimers());

        $this->metrics->reset();
        $this->assertCount(0, $this->metrics->getTimers());
    }

    public function testLapMeasuresIncrementalTime(): void
    {
        $this->metrics->start('lap');
        usleep(30000);
        $lap1 = $this->metrics->lap('lap');
        usleep(30000);
        $lap2 = $this->metrics->lap('lap');

        $this->assertGreaterThan(0.02, $lap1);
        $this->assertGreaterThan(0.02, $lap2);
        $this->assertNotEquals($lap1, $lap2);
    }

    public function testStopThrowsExceptionIfTimerNotStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'default' has not been started.");
        $this->metrics->stop('default');
    }

    public function testLapThrowsExceptionIfTimerNotStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'default' has not been started.");
        $this->metrics->lap('default');
    }

    public function testGetDurationThrowsExceptionIfTimerNotStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'default' has not been started.");
        $this->metrics->getDuration('default');
    }

    public function testGetMemoryUsageThrowsExceptionIfTimerNotStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'default' has not been started.");
        $this->metrics->getMemoryUsage('default');
    }

    public function testGetPeakMemoryUsageThrowsExceptionIfTimerNotStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'default' has not been started.");
        $this->metrics->getPeakMemoryUsage('default');
    }

    public function testGetPeakMemoryUsageUsesMemoryGetPeakUsageIfEndPeakNotSet(): void
    {
        $this->metrics->start('default');
        $result = $this->metrics->getPeakMemoryUsage('default', false);
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testResetWithNameUnsetsSpecificTimer(): void
    {
        $this->metrics->start('timer1');
        $this->metrics->start('timer2');
        $this->assertCount(2, $this->metrics->getTimers());
        $this->metrics->reset('timer1');
        $timers = $this->metrics->getTimers();
        $this->assertNotContains('timer1', $timers);
        $this->assertContains('timer2', $timers);
        $this->assertCount(1, $timers);
    }


    public function testReportThrowsExceptionIfTimerNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Timer 'nonexistent' not found.");
        $this->metrics->report('nonexistent');
    }

    public function testReportAllGeneratesOutput(): void
    {
        $this->metrics->measure(fn() => usleep(10000), 'timer1');
        $output = $this->metrics->reportAll();
        $this->assertStringContainsString('timer1', $output);
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }
}
