<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

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
}
