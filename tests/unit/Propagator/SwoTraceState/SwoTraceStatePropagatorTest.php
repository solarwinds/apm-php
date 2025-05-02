<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Propagator\SwoTraceState;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\SDK\Trace\RandomIdGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Propagator\SwoTraceState\SwoTraceStatePropagator;

#[CoversClass(SwoTraceStatePropagator::class)]
class SwoTraceStatePropagatorTest extends TestCase
{
    private TextMapPropagatorInterface $propagator;

    public function test_fields(): void
    {
        $this->assertSame(SwoTraceStatePropagator::FIELDS, $this->propagator->fields());
    }

    public function test_inject_empty_baggage(): void
    {
        $carrier = [];
        $this->propagator->inject($carrier);
        $this->assertEmpty($carrier);
    }

    public function test_inject_not_sampled_no_trace_state(): void
    {
        $carrier = [];
        $generator = new RandomIdGenerator();
        $traceId = $generator->generateTraceId();
        $spanId = $generator->generateSpanId();
        $spanContext = SpanContext::create($traceId, $spanId, TraceFlags::DEFAULT);
        $span = Span::wrap($spanContext);
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue($span));
        $this->assertEquals(['tracestate' => 'sw=' . $spanId . '-00'], $carrier);
    }

    public function test_inject_sampled_no_trace_state(): void
    {
        $carrier = [];
        $generator = new RandomIdGenerator();
        $traceId = $generator->generateTraceId();
        $spanId = $generator->generateSpanId();
        $spanContext = SpanContext::create($traceId, $spanId, TraceFlags::SAMPLED);
        $span = Span::wrap($spanContext);
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue($span));
        $this->assertEquals(['tracestate' => 'sw=' . $spanId . '-01'], $carrier);
    }

    public function test_inject_sampled_trace_state(): void
    {
        $carrier = [];
        $generator = new RandomIdGenerator();
        $traceId = $generator->generateTraceId();
        $spanId = $generator->generateSpanId();
        $spanContext = SpanContext::create($traceId, $spanId, TraceFlags::SAMPLED, new TraceState('a=b,c=d'));
        $span = Span::wrap($spanContext);
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue($span));
        $this->assertEquals(['tracestate' => 'sw=' . $spanId . '-01' . ',a=b,c=d'], $carrier);
    }

    public function test_inject_sampled_trace_state_replace_sw(): void
    {
        $carrier = [];
        $generator = new RandomIdGenerator();
        $traceId = $generator->generateTraceId();
        $spanId = $generator->generateSpanId();
        $spanContext = SpanContext::create($traceId, $spanId, TraceFlags::SAMPLED, new TraceState('a=b,sw=' . $generator->generateSpanId() . '-00,c=d'));
        $span = Span::wrap($spanContext);
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue($span));
        $this->assertEquals(['tracestate' => 'sw=' . $spanId . '-01' . ',a=b,c=d'], $carrier);
    }

    public function test_extract_empty_baggage(): void
    {
        $this->assertEquals(Context::getCurrent(), $this->propagator->extract([]));
    }

    public function test_extract(): void
    {
        $carrier = [];
        $context = $this->propagator->extract($carrier);
        $this->assertEquals($context, Context::getCurrent());
    }

    protected function setUp(): void
    {
        $this->propagator = SwoTraceStatePropagator::getInstance();
    }
}
