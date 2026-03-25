<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanSuppression\SamplerSuppressionStrategy;

use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppressionStrategy;
use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppressor;

final class SamplerSuppressionStrategy implements SpanSuppressionStrategy
{
    #[\Override]
    public function getSuppressor(string $name, ?string $version, ?string $schemaUrl): SpanSuppressor
    {
        static $suppressor = new SamplerSuppressor();

        return $suppressor;
    }
}
