<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;

class SampleState
{
    public int $decision;
    public AttributesInterface $attributes;
    public ?Settings $settings;
    public ?string $traceState;
    public RequestHeaders $headers;
    public ?TraceOptionsWithResponse $traceOptions;

    public function __construct(
        int $decision,
        AttributesInterface $attributes,
        ?Settings $settings,
        ?string $traceState,
        RequestHeaders $headers,
        ?TraceOptionsWithResponse $traceOptions,
    ) {
        $this->decision = $decision;
        $this->attributes = $attributes;
        $this->settings = $settings;
        $this->traceState = $traceState;
        $this->headers = $headers;
        $this->traceOptions = $traceOptions;
    }

    public function __toString(): string
    {
        return sprintf(
            'SampleState(decision=%d, attributes=%s, settings=%s, traceState=%s, headers=%s, traceOptions=%s)',
            $this->decision,
            implode(',', $this->attributes->toArray()),
            $this->settings ?? 'null',
            $this->traceState ?? 'null',
            $this->headers,
            $this->traceOptions ?? 'null'
        );
    }
}
