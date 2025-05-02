<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\Counters;

#[CoversClass(Counters::class)]
class CountersTest extends TestCase
{
    private Counters $counters;

    public function test_counters_initialization(): void
    {
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getRequestCount());
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getSampleCount());
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getTraceCount());
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getThroughTraceCount());
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getTriggeredTraceCount());
        $this->assertInstanceOf(CounterInterface::class, $this->counters->getTokenBucketExhaustionCount());
    }

    protected function setUp(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $meter = $this->createMock(MeterInterface::class);

        $meterProvider->method('getMeter')->willReturn($meter);

        $counter = $this->createMock(CounterInterface::class);
        $meter->method('createCounter')->willReturn($counter);

        $this->counters = new Counters($meterProvider);
    }
}
