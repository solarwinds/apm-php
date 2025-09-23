<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ResponsePropagator\XTraceOptionsResponse;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;

/**
 * Provides a ResponsePropagator for X-Trace-Options-Response headers
 */
class XTraceOptionsResponsePropagator implements ResponsePropagatorInterface
{
    const X_TRACE_OPTIONS_RESPONSE = 'X-Trace-Options-Response';
    private static ?self $instance = null;

    public function fields(): array
    {
        return [
            self::X_TRACE_OPTIONS_RESPONSE,
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

        $traceState = $spanContext->getTraceState();
        if ($traceState !== null) {
            $xtrace_options_response = $traceState->get(KnownValues::VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE);
            if ($xtrace_options_response !== null) {
                $replaced = str_replace('....', ',', $xtrace_options_response);
                $final = str_replace('####', '=', $replaced);
                $setter->set($carrier, self::X_TRACE_OPTIONS_RESPONSE, $final);
            }
        }
    }
}
