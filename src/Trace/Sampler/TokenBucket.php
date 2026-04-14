<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;

class TokenBucket
{
    use LogsMessagesTrait;
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
            $this->tokens = max(0, $this->tokens);
        } else {
            // Always full if a brand-new token bucket
            $this->tokens = $this->capacity;
        }
        $this->lastUsed = $now;
    }

    public function updateFromCache(float $cachedCapacity, float $cachedRate, float $cachedTokens, float $cachedLastUsed): void
    {
        $this->capacity = $cachedCapacity;
        $this->rate = $cachedRate;
        $this->tokens = $cachedTokens;
        $this->lastUsed = $cachedLastUsed;
    }

    public function update(?float $newCapacity = null, ?float $newRate = null): void
    {
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
                $this->tokens = min($this->tokens, $newCapacity);
                $this->lastUsed = microtime(true);
            } elseif ($this->capacity !== $newCapacity) {
                // Adjust tokens due to ongoing bucket capacity updates,
                // including re-enabling a previously used bucket from 0 capacity.
                // First, calculate token till now
                $now = microtime(true);
                $elapsed = $now - $this->lastUsed;
                $this->tokens += $elapsed * $this->rate;
                // Second, adjust tokens based on new capacity
                $this->tokens += $diff;
                // Third, cap tokens to new capacity if needed
                $this->tokens = min($this->tokens, $newCapacity);
                // Forth, raise tokens to 0 if needed
                $this->tokens = max(0, $this->tokens);
                $this->lastUsed = $now;
            }
            $this->capacity = $newCapacity;
        }
    }

    public function consume(float $tokens = 1): bool
    {
        $this->calculateTokens();
        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;
            if ($this->capacity > 0) {
                $this->logDebug('Consumed ' . $tokens . ' token (' . round($this->tokens / $this->capacity * 100, 2) . '% remaining)');
            } else {
                $this->logDebug('Consumed ' . $tokens . ' token (' . $this->tokens . ' remaining)');
            }

            return true;
        }
        $this->logDebug('Token consumption failed: requested=' . $tokens . ', available=' . $this->tokens . ', capacity=' . $this->capacity);

        return false;
    }

    public function __toString(): string
    {
        return sprintf('TokenBucket(capacity=%.2f, rate=%.2f, tokens=%.2f)', $this->capacity, $this->rate, $this->tokens);
    }
}
