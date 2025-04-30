<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use Closure;

class Configuration
{
    private bool $enabled;
    private string $service;
    private string $collector;
    private array $headers;
    private ?bool $tracingMode;
    private bool $triggerTraceEnabled;
    private ?Closure $transactionName;
    private array $transactionSettings;

    public function __construct(
        bool $enabled,
        string $service,
        string $collector,
        array $headers,
        ?bool $tracingMode,
        bool $triggerTraceEnabled,
        ?Closure $transactionName,
        array $transactionSettings,
    ) {
        $this->enabled = $enabled;
        $this->service = $service;
        $this->collector = $collector;
        $this->headers = $headers;
        $this->tracingMode = $tracingMode;
        $this->triggerTraceEnabled = $triggerTraceEnabled;
        $this->transactionName = $transactionName;
        $this->transactionSettings = $transactionSettings;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $value): void
    {
        $this->enabled = $value;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $value): void
    {
        $this->service = $value;
    }

    public function getCollector(): string
    {
        return $this->collector;
    }

    public function setCollector(string $value): void
    {
        $this->collector = $value;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $value): void
    {
        $this->headers = $value;
    }

    public function getTracingMode(): ?bool
    {
        return $this->tracingMode;
    }

    public function setTracingMode(?bool $value): void
    {
        $this->tracingMode = $value;
    }

    public function isTriggerTraceEnabled(): bool
    {
        return $this->triggerTraceEnabled;
    }

    public function setTriggerTraceEnabled(bool $value): void
    {
        $this->triggerTraceEnabled = $value;
    }

    public function getTransactionName(): ?Closure
    {
        return $this->transactionName;
    }

    public function setTransactionName(?Closure $value): void
    {
        $this->transactionName = $value;
    }

    public function getTransactionSettings(): array
    {
        return $this->transactionSettings;
    }

    public function setTransactionSettings(array $value): void
    {
        $this->transactionSettings = $value;
    }

    public function __toString(): string
    {
        return sprintf(
            'Configuration(enabled=%s, service=%s, collector=%s, headers=%s, tracingMode=%s, triggerTraceEnabled=%s, transactionName=%s, transactionSettings=%s)',
            $this->enabled ? 'true' : 'false',
            $this->service,
            $this->collector,
            json_encode($this->headers),
            $this->tracingMode !== null ? ($this->tracingMode ? 'true' : 'false') : 'null',
            $this->triggerTraceEnabled ? 'true' : 'false',
            $this->transactionName !== null ? 'Closure' : 'null',
            json_encode($this->transactionSettings)
        );
    }
}
