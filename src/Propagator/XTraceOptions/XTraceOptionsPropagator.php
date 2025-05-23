<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Propagator\XTraceOptions;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationGetterInterface;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

class XTraceOptionsPropagator implements TextMapPropagatorInterface
{
    public const XTRACEOPTIONS = 'x-trace-options';
    public const XTRACEOPTIONSSIGNATURE = 'x-trace-options-signature';
    public const XTRACEOPTIONSRESPONSE = 'x-trace-options-response';

    public const FIELDS = [
        self::XTRACEOPTIONS,
        self::XTRACEOPTIONSSIGNATURE,
        self::XTRACEOPTIONSRESPONSE,
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
        $xTraceOptionsResponseBaggage = XTraceOptionsResponseBaggage::fromContext($context);
        if ($xTraceOptionsResponseBaggage->isEmpty()) {
            return;
        }
        $response = '';
        foreach ($xTraceOptionsResponseBaggage->getAll() as $key => $entry) {
            $value = urlencode((string) $entry->getValue());
            $response .= "{$key}={$value};";
        }
        $response = rtrim($response, ';');
        if (!empty($response)) {
            $setter->set($carrier, self::XTRACEOPTIONSRESPONSE, $response);
        }
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
        $getter ??= ArrayAccessGetterSetter::getInstance();
        $context ??= Context::getCurrent();
        $xTraceOptions = $getter->get($carrier, self::XTRACEOPTIONS);
        if ($xTraceOptions === null) {
            return $context;
        }
        $xTraceOptionsBaggageBuilder = XTraceOptionsBaggage::getBuilder();
        $xTraceOptionsBaggageBuilder->set(self::XTRACEOPTIONS, $xTraceOptions);
        $xTraceOptionsSignature = $getter->get($carrier, self::XTRACEOPTIONSSIGNATURE);
        if ($xTraceOptionsSignature !== null) {
            $xTraceOptionsBaggageBuilder->set(self::XTRACEOPTIONSSIGNATURE, $xTraceOptionsSignature);
        }

        return $context->withContextValue($xTraceOptionsBaggageBuilder->build());
    }
}
