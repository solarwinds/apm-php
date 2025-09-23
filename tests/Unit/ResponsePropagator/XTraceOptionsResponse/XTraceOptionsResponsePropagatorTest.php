<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\ResponsePropagator\XTraceOptionsResponse;

use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanContextValidator;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Trace\Span;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\ResponsePropagator\XTraceOptionsResponse\XTraceOptionsResponsePropagator;

#[CoversClass(XTraceOptionsResponsePropagator::class)]
class XTraceOptionsResponsePropagatorTest extends TestCase
{
    private const TRACE_ID = '5759e988bd862e3fe1be46a994272793';
    private const SPAN_ID = '53995c3f42cd8ad8';
    private const TRACESTATE_XTRACEOPTIONSRESPONSE = 'bar####baz....qux####quux';
    private const RECOVERED_XTRACEOPTIONSRESPONSE = 'bar=baz,qux=quux';
    private XTraceOptionsResponsePropagator $xTraceOptionsResponsePropagator;

    #[\Override]
    protected function setUp(): void
    {
        $this->xTraceOptionsResponsePropagator = XTraceOptionsResponsePropagator::getInstance();
    }

    public function test_fields()
    {
        $this->assertSame($this->xTraceOptionsResponsePropagator->fields(), [XTraceOptionsResponsePropagator::X_TRACE_OPTIONS_RESPONSE]);
    }

    public function test_inject_trace_id_with_trace_state()
    {
        $carrier = [];
        $this->xTraceOptionsResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(self::TRACE_ID, self::SPAN_ID, TraceFlags::SAMPLED, new TraceState(KnownValues::VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE . '=' . self::TRACESTATE_XTRACEOPTIONSRESPONSE)),
                Context::getCurrent()
            )
        );

        $this->assertSame(
            [XTraceOptionsResponsePropagator::X_TRACE_OPTIONS_RESPONSE => self::RECOVERED_XTRACEOPTIONSRESPONSE],
            $carrier
        );
    }

    public function test_inject_trace_id_not_sampled_with_trace_state()
    {
        $carrier = [];
        $this->xTraceOptionsResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(self::TRACE_ID, self::SPAN_ID, TraceFlags::DEFAULT, new TraceState(KnownValues::VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE . '=' . self::TRACESTATE_XTRACEOPTIONSRESPONSE)),
                Context::getCurrent()
            )
        );

        $this->assertSame(
            [XTraceOptionsResponsePropagator::X_TRACE_OPTIONS_RESPONSE => self::RECOVERED_XTRACEOPTIONSRESPONSE],
            $carrier
        );
    }

    public function test_inject_trace_id_with_invalid_span_context()
    {
        $carrier = [];
        $this->xTraceOptionsResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(SpanContextValidator::INVALID_TRACE, SpanContextValidator::INVALID_SPAN, TraceFlags::SAMPLED, new TraceState(KnownValues::VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE . '=' . self::TRACESTATE_XTRACEOPTIONSRESPONSE)),
                Context::getCurrent()
            )
        );

        $this->assertEmpty($carrier);
    }

    private function withSpanContext(SpanContextInterface $spanContext, ContextInterface $context): ContextInterface
    {
        return $context->withContextValue(Span::wrap($spanContext));
    }
}
