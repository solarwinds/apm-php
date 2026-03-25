<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanSuppression\SamplerSuppressionStrategy;

use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppression;
use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppressor;

final class SamplerSuppressor implements SpanSuppressor
{
    #[\Override]
    public function resolveSuppression(int $spanKind, array $attributes): SpanSuppression
    {
        return new SamplerSuppression(SamplerSuppressionContextKey::suppress());
    }
}
