<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

class BucketState
{
    public function __construct(
        private readonly float $capacity,
        private readonly float $rate,
        private readonly ?float $tokens = null,
        private readonly ?float $lastUsed = null,
    ) {
    }

    public function getCapacity(): float
    {
        return $this->capacity;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getToken(): float | null
    {
        return $this->tokens;
    }

    public function getLastUsed(): float | null
    {
        return $this->lastUsed;
    }
}
