<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class BucketSettings
{
    public function __construct(
        public float $capacity,
        public float $rate,
    ) {
    }
}
