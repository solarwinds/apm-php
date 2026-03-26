<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

class BucketState
{
    public function __construct(
        private readonly ?float $currentTokens = null,
        private readonly ?float $lastUsed = null,
    ) {
    }

    public function getCurrentToken(): float | null
    {
        return $this->currentTokens;
    }

    public function getLastUsed(): float | null
    {
        return $this->lastUsed;
    }
}
