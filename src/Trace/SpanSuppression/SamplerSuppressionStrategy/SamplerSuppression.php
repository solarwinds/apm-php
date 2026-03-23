<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanSuppression\SamplerSuppressionStrategy;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;
use OpenTelemetry\SDK\Trace\SpanSuppression\SpanSuppression;

final class SamplerSuppression implements SpanSuppression
{
    use LogsMessagesTrait;
    public function __construct(
        private readonly ContextKeyInterface $contextKey,
    ) {
    }

    #[\Override]
    public function isSuppressed(ContextInterface $context): bool
    {
        $this->logInfo('isSuppressed is called: ' . ($context->get($this->contextKey)? 'true' : 'false'));

        return $context->get($this->contextKey) === true;
    }

    #[\Override]
    public function suppress(ContextInterface $context): ContextInterface
    {
        $this->logInfo('suppress is called');

        return $context->with($this->contextKey, true);
    }
}
