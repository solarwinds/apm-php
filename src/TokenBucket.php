<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class TokenBucket
{
    private float $capacity;
    private float $rate;
    private float $tokens;
    private float $lastUsed;

    public function __construct(float $capacity = 0, float $rate = 0)
    {
        $this->capacity = $capacity;
        $this->rate = $rate;
        $this->tokens = $capacity;
        $this->lastUsed = microtime(true);
    }

    public function getCapacity(): float
    {
        return $this->capacity;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getTokens(): float
    {
        $this->calculateTokens();

        return $this->tokens;
    }

    private function calculateTokens(): void
    {
        $now = microtime(true);
        $elapsed = $now - $this->lastUsed;
        $this->lastUsed = $now;
        $this->tokens += $elapsed * $this->rate;
        $this->tokens = min($this->tokens, $this->capacity);
    }

    public function update(?float $newCapacity = null, ?float $newRate = null): void
    {
        $this->calculateTokens();
        if ($newCapacity !== null) {
            $newCapacity = max(0.0, $newCapacity);
            $diff = $newCapacity - $this->capacity;
            $this->capacity = $newCapacity;
            $this->tokens += $diff;
            $this->tokens = max(0.0, $this->tokens);
        }
        if ($newRate !== null) {
            $newRate = max(0.0, $newRate);
            $this->rate = $newRate;
        }
    }

    public function consume(float $tokens = 1): bool
    {
        $this->calculateTokens();
        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;

            return true;
        }

        return false;
    }

    public function __toString()
    {
        return sprintf('TokenBucket(capacity=%.2f, rate=%.2f)', $this->capacity, $this->rate);
    }
}
