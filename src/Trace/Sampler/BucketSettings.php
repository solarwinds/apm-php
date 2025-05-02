<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

class BucketSettings
{
    public function __construct(
        private readonly float $capacity,
        private readonly float $rate,
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
}
