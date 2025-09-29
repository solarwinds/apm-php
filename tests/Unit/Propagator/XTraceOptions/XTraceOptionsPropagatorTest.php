<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Propagator\XTraceOptions;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggage;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsPropagator;

#[CoversClass(XTraceOptionsPropagator::class)]
class XTraceOptionsPropagatorTest extends TestCase
{
    private TextMapPropagatorInterface $propagator;

    public function test_fields(): void
    {
        $this->assertSame(XTraceOptionsPropagator::FIELDS, $this->propagator->fields());
    }

    public function test_inject_empty_baggage(): void
    {
        $carrier = [];
        $this->propagator->inject($carrier);
        $this->assertEmpty($carrier);
    }

    public function test_extract_empty_baggage(): void
    {
        $this->assertEquals(Context::getCurrent(), $this->propagator->extract([]));
    }

    public function test_extract_x_trace_options_baggage_options_only(): void
    {
        $carrier = [
            'x-trace-options' => 'foo',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue(!$bag->isEmpty());
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')?->getValue());
        $this->assertNull($bag->getEntry('x-trace-options-signature'));
    }

    public function test_extract_x_trace_options_baggage_with_signature(): void
    {
        $carrier = [
            'x-trace-options' => 'foo',
            'x-trace-options-signature' => 'bar',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue(!$bag->isEmpty());
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')?->getValue());
        $this->assertEquals('bar', $bag->getEntry('x-trace-options-signature')?->getValue());
    }

    public function test_extract_x_trace_options_baggage_signature_only(): void
    {
        $carrier = [
            'x-trace-options-signature' => 'bar',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue($bag->isEmpty());
    }

    protected function setUp(): void
    {
        $this->propagator = XTraceOptionsPropagator::getInstance();
    }

    public function test_get_instance_returns_singleton(): void
    {
        $instance1 = XTraceOptionsPropagator::getInstance();
        $instance2 = XTraceOptionsPropagator::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function test_extract_with_unrelated_headers(): void
    {
        $carrier = [
            'unrelated-header' => 'value',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue($bag->isEmpty());
    }

    public function test_extract_with_signature_but_no_options(): void
    {
        $carrier = [
            'x-trace-options-signature' => 'bar',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue($bag->isEmpty());
    }
}
