<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Propagator\SwoTraceState;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationGetterInterface;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

class SwoTraceStatePropagator implements TextMapPropagatorInterface
{
    public const TRACESTATE = 'tracestate';

    public const FIELDS = [
        self::TRACESTATE,
    ];

    private static ?self $instance = null;

    public function fields(): array
    {
        return self::FIELDS;
    }

    public function inject(mixed &$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void
    {
        $setter ??= ArrayAccessGetterSetter::getInstance();
        $context ??= Context::getCurrent();
        $spanContext = Span::fromContext($context)->getContext();
        if (!$spanContext->isValid()) {
            return;
        }
        $swTraceState = $spanContext->getSpanId() . '-' . ($spanContext->isSampled() ? '01' : '00');
        $traceState = $spanContext->getTraceState();
        if ($traceState === null) {
            $traceState = new TraceState();
        }
        $updatedTraceState = $traceState->without('sw')->with('sw', $swTraceState);
        $setter->set($carrier, self::TRACESTATE, (string) $updatedTraceState);
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function extract(mixed $carrier, ?PropagationGetterInterface $getter = null, ?ContextInterface $context = null): ContextInterface
    {
        $context ??= Context::getCurrent();

        return $context;
    }
}
