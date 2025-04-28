<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

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

    /** {@inheritdoc} */
    public function fields(): array
    {
        return self::FIELDS;
    }

    /** {@inheritdoc} */
    public function inject(&$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void
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

    /** {@inheritdoc} */
    public function extract($carrier, ?PropagationGetterInterface $getter = null, ?ContextInterface $context = null): ContextInterface
    {
        $context ??= Context::getCurrent();

        return $context;
    }
}
