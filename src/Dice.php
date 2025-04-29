<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class Dice
{
    private int $scale;
    private int $rate;

    public function __construct(int $scale, int $rate = 0)
    {
        $this->scale = $scale;
        $this->rate = $rate;
    }

    public function getRate(): int
    {
        return $this->rate;
    }

    public function setRate(int $newRate): void
    {
        $this->rate = max(0, min($this->scale, $newRate));
    }

    public function update(int $newScale, ?int $newRate = null): void
    {
        $this->scale = $newScale;
        if ($newRate !== null) {
            $this->setRate($newRate);
        }
    }

    public function roll(): bool
    {
        if ($this->scale <= 0) {
            return false;
        }

        /**
         * random_int() return a cryptographically secure, uniformly selected integer from the closed interval [min, max]
         */
        return random_int(0, $this->scale - 1) < $this->rate;
    }
}
