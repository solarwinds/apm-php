<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use Closure;

class TransactionSetting
{
    private bool $tracing;
    private Closure $matcher;

    public function __construct(bool $tracing, Closure $matcher)
    {
        $this->tracing = $tracing;
        $this->matcher = $matcher;
    }

    public function getTracing(): bool
    {
        return $this->tracing;
    }

    public function setTracing(bool $value): void
    {
        $this->tracing = $value;
    }

    public function getMatcher(): Closure
    {
        return $this->matcher;
    }

    public function setMatcher(Closure $value): void
    {
        $this->matcher = $value;
    }
}
