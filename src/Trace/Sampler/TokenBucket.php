<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

class TokenBucket
{
    private float $capacity;
    private float $rate;
    private float $tokens;
    private ?float $lastUsed = null;

    public function __construct(float $capacity = 0, float $rate = 0)
    {
        $this->capacity = $capacity;
        $this->rate = $rate;
        $this->tokens = $capacity;
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

    public function getLastUsed(): ?float
    {
        return $this->lastUsed;
    }

    public function calculateTokens(): void
    {
        $now = microtime(true);
        if ($this->lastUsed !== null) {
            $elapsed = $now - $this->lastUsed;
            $this->tokens += $elapsed * $this->rate;
            $this->tokens = min($this->tokens, $this->capacity);
        } else {
            // Always full if a brand-new token bucket
            $this->tokens = $this->capacity;
        }
        $this->lastUsed = $now;
    }

    public function update(?float $newCapacity = null, ?float $newRate = null, ?float $cachedTokens = null, ?float $cachedLastUsed = null): void
    {
        if ($cachedTokens !== null) {
            $cachedTokens = max(0, $cachedTokens);
            $this->tokens = $cachedTokens;
        }
        if ($cachedLastUsed !== null) {
            $cachedLastUsed = max(0, $cachedLastUsed);
            $this->lastUsed = $cachedLastUsed;
        }
        if ($newRate !== null) {
            $newRate = max(0, $newRate);
            $this->rate = $newRate;
        }
        if ($newCapacity !== null) {
            $newCapacity = max(0, $newCapacity);
            $diff = $newCapacity - $this->capacity;
            if ($this->lastUsed === null) {
                // Always full if a brand-new token bucket
                $this->tokens += $diff;
            } elseif ($this->capacity > 0) {
                // Adjust tokens due to ongoing bucket capacity updates
                $this->tokens += $diff;
            }
            $this->capacity = $newCapacity;
            $this->tokens = min($this->tokens, $this->capacity);
            $this->lastUsed = microtime(true);
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
