<?php

declare(strict_types=1);

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\XTraceOptionsBaggage;
use Solarwinds\ApmPhp\XTraceOptionsPropagator;
use Solarwinds\ApmPhp\XTraceOptionsResponseBaggage;

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

    public function test_inject_x_trace_options_response_baggage(): void
    {
        $carrier = [];
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue(XTraceOptionsResponseBaggage::getBuilder()->set('trigger-trace', 'ok')->set('foo', 'bar')->build()));
        $this->assertSame(['x-trace-options-response' => 'trigger-trace=ok;foo=bar'], $carrier);
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
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')->getValue());
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
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')->getValue());
        $this->assertEquals('bar', $bag->getEntry('x-trace-options-signature')->getValue());
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
}
