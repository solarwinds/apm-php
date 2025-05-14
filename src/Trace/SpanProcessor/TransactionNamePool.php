<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

class TransactionNamePool
{
    private array $pool = [];    // Stores transaction name => [timestamp,  name]
    private array $minHeap = []; // Store timestamp => [transaction names]
    private readonly int $maxSize;
    private readonly int $ttl;
    private readonly int $maxLength;
    private readonly string $default;

    public function __construct(
        int $maxSize = 200,
        int $ttl = 60,
        int $maxLength = 256,
        string $default = 'other',
    ) {
        $this->maxSize = $maxSize;
        $this->ttl = $ttl;
        $this->maxLength = $maxLength;
        $this->default = $default;
    }

    public function register(string $name): string
    {
        $this->housekeeping();
        $name = substr($name, 0, $this->maxLength);

        if (isset($this->pool[$name])) {
            [$old_timestamp, $name] = $this->pool[$name];
            $new_timestamp = time();
            $this->pool[$name] = [$new_timestamp, $name];
            $this->updateMinHeap($name, $old_timestamp, $new_timestamp);

            return $name;
        }

        if (count($this->pool) >= $this->maxSize) {
            return $this->default;
        }
        $timestamp = time();
        $this->pool[$name] = [$timestamp, $name];
        $this->minHeap[$timestamp][] = $name;

        return $name;
    }

    private function updateMinHeap(string $name, int $old_timestamp, int $new_timestamp): void
    {
        if (isset($this->minHeap[$old_timestamp])) {
            $index = array_search($name, $this->minHeap[$old_timestamp], true);
            if ($index !== false) {
                unset($this->minHeap[$old_timestamp][$index]);
                if (empty($this->minHeap[$old_timestamp])) {
                    unset($this->minHeap[$old_timestamp]);
                }
            }
        }
        $this->minHeap[$new_timestamp][] = $name;
    }

    public function housekeeping(): void
    {
        $now = time();
        foreach ($this->minHeap as $timestamp => $names) {
            if ((int) $timestamp + $this->ttl < $now) {
                foreach ($names as $name) {
                    unset($this->pool[$name]);
                }
                unset($this->minHeap[$timestamp]);
            }
        }
    }
}
