<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class LocalSettings
{
    private ?TracingMode $tracingMode;
    private bool $triggerMode;

    public function __construct(?TracingMode $tracingMode, bool $triggerMode)
    {
        $this->tracingMode = $tracingMode;
        $this->triggerMode = $triggerMode;
    }

    public function getTracingMode(): ?TracingMode
    {
        return $this->tracingMode;
    }

    public function setTracingMode(?TracingMode $tracingMode): void
    {
        $this->tracingMode = $tracingMode;
    }

    public function getTriggerMode(): bool
    {
        return $this->triggerMode;
    }

    public function setTriggerMode(bool $triggerMode): void
    {
        $this->triggerMode = $triggerMode;
    }
}
