<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanSuppression\SamplerSuppressionStrategy;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;
use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppression;

final class SamplerSuppression implements SpanSuppression
{
    public function __construct(
        private readonly ContextKeyInterface $contextKey,
    ) {
    }

    #[\Override]
    public function isSuppressed(ContextInterface $context): bool
    {
        return $context->get($this->contextKey) === true;
    }

    #[\Override]
    public function suppress(ContextInterface $context): ContextInterface
    {
        return $context->with($this->contextKey, true);
    }
}
