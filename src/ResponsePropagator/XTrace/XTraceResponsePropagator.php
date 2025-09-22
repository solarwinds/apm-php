<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ResponsePropagator\XTrace;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;

/**
 * Provides a ResponsePropagator for X-Trace headers
 */
class XTraceResponsePropagator implements ResponsePropagatorInterface
{
    const IS_SAMPLED = '01';
    const NOT_SAMPLED = '00';
    const SUPPORTED_VERSION = '00';
    const X_TRACE = 'X-Trace';
    private static ?self $instance = null;

    public function fields(): array
    {
        return [
            self::X_TRACE,
        ];
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @suppress PhanUndeclaredClassAttribute
     */
    #[\Override]
    public function inject(&$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void
    {
        $setter = $setter ?? ArrayAccessGetterSetter::getInstance();
        $context = $context ?? Context::getCurrent();
        $spanContext = Span::fromContext($context)->getContext();

        if (!$spanContext->isValid()) {
            return;
        }

        $traceId = $spanContext->getTraceId();
        $spanId = $spanContext->getSpanId();

        $samplingFlag = $spanContext->isSampled() ? self::IS_SAMPLED : self::NOT_SAMPLED;

        $header = self::SUPPORTED_VERSION . '-' . $traceId . '-' . $spanId . '-' . $samplingFlag;
        $setter->set($carrier, self::X_TRACE, $header);
    }
}
