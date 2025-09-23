<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\ResponsePropagator\XTrace;

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
use Solarwinds\ApmPhp\ResponsePropagator\XTrace\XTraceResponsePropagator;

#[CoversClass(XTraceResponsePropagator::class)]
class XTraceResponsePropagatorTest extends TestCase
{
    private const VERSION = '00';
    private const TRACE_ID = '5759e988bd862e3fe1be46a994272793';
    private const SPAN_ID = '53995c3f42cd8ad8';
    private const IS_SAMPLED = '01';
    private const NOT_SAMPLED = '00';
    private const XTRACE_HEADER_SAMPLED = self::VERSION . '-' . self::TRACE_ID . '-' . self::SPAN_ID . '-' . self::IS_SAMPLED;
    private const XTRACE_HEADER_NOT_SAMPLED = self::VERSION . '-' . self::TRACE_ID . '-' . self::SPAN_ID . '-' . self::NOT_SAMPLED;
    private XTraceResponsePropagator $xTraceResponsePropagator;

    #[\Override]
    protected function setUp(): void
    {
        $this->xTraceResponsePropagator = XTraceResponsePropagator::getInstance();
    }

    public function test_fields()
    {
        $this->assertSame($this->xTraceResponsePropagator->fields(), [XTraceResponsePropagator::X_TRACE]);
    }

    public function test_inject_valid_sampled_trace_id()
    {
        $carrier = [];
        $this->xTraceResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(self::TRACE_ID, self::SPAN_ID, TraceFlags::SAMPLED),
                Context::getCurrent()
            )
        );

        $this->assertSame(
            [XTraceResponsePropagator::X_TRACE => self::XTRACE_HEADER_SAMPLED],
            $carrier
        );
    }

    public function test_inject_valid_not_sampled_trace_id()
    {
        $carrier = [];
        $this->xTraceResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(self::TRACE_ID, self::SPAN_ID),
                Context::getCurrent()
            )
        );

        $this->assertSame(
            [XTraceResponsePropagator::X_TRACE => self::XTRACE_HEADER_NOT_SAMPLED],
            $carrier
        );
    }

    public function test_inject_trace_id_with_trace_state()
    {
        $carrier = [];
        $this->xTraceResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(self::TRACE_ID, self::SPAN_ID, TraceFlags::SAMPLED, new TraceState('vendor1=opaqueValue1')),
                Context::getCurrent()
            )
        );

        $this->assertSame(
            [XTraceResponsePropagator::X_TRACE => self::XTRACE_HEADER_SAMPLED],
            $carrier
        );
    }

    public function test_inject_trace_id_with_invalid_span_context()
    {
        $carrier = [];
        $this->xTraceResponsePropagator->inject(
            $carrier,
            null,
            $this->withSpanContext(
                SpanContext::create(SpanContextValidator::INVALID_TRACE, SpanContextValidator::INVALID_SPAN, TraceFlags::SAMPLED, new TraceState('vendor1=opaqueValue1')),
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
