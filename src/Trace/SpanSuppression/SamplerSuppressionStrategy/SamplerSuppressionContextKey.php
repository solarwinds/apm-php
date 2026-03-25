<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanSuppression\SamplerSuppressionStrategy;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextKeyInterface;

final class SamplerSuppressionContextKey
{
    public static function suppress(): ContextKeyInterface
    {
        static $instance;

        return $instance ??= Context::createKey('solarwinds-suppress');
    }
}
