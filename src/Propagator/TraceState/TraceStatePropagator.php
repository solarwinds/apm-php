<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Propagator\TraceState;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationGetterInterface;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;

class TraceStatePropagator implements TextMapPropagatorInterface
{
    public const TRACESTATE = 'tracestate';
    const SW = 'sw';

    public const FIELDS = [
        self::TRACESTATE,
    ];

    private static ?self $instance = null;

    #[\Override]
    public function fields(): array
    {
        return self::FIELDS;
    }

    #[\Override]
    public function inject(mixed &$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void
    {
        $setter ??= ArrayAccessGetterSetter::getInstance();
        $context ??= Context::getCurrent();
        $spanContext = Span::fromContext($context)->getContext();
        if (!$spanContext->isValid()) {
            return;
        }
        $swTraceState = sprintf('%s-%02x', $spanContext->getSpanId(), $spanContext->getTraceFlags());
        $traceState = $spanContext->getTraceState();
        if (!$traceState instanceof \OpenTelemetry\API\Trace\TraceStateInterface) {
            $traceState = new TraceState();
        }
        $updatedTraceState = $traceState->without(self::SW)->with(self::SW, $swTraceState);
        // Remove XTRACE_OPTIONS_RESPONSE if present
        $updatedTraceState = $updatedTraceState->without(KnownValues::VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE);
        $setter->set($carrier, self::TRACESTATE, (string) $updatedTraceState);
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof \Solarwinds\ApmPhp\Propagator\TraceState\TraceStatePropagator) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    #[\Override]
    public function extract(mixed $carrier, ?PropagationGetterInterface $getter = null, ?ContextInterface $context = null): ContextInterface
    {
        $context ??= Context::getCurrent();

        return $context;
    }
}
