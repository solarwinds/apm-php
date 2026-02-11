<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Configuration;

class Configuration
{
    private bool $enabled;
    private string $service;
    private string $collector;
    private string $token;
    private ?bool $tracingMode;
    private bool $triggerTraceEnabled;
    private ?string $transactionName;
    private array $transactionSettings;

    public function __construct(
        bool $enabled,
        string $service,
        string $collector,
        string $token,
        ?bool $tracingMode,
        bool $triggerTraceEnabled,
        ?string $transactionName,
        array $transactionSettings,
    ) {
        $this->enabled = $enabled;
        $this->service = $service;
        $this->collector = $collector;
        $this->token = $token;
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

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $value): void
    {
        $this->token = $value;
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

    public function getTransactionName(): ?string
    {
        return $this->transactionName;
    }

    public function setTransactionName(?string $value): void
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
        $token = $this->token;
        $tokenLength = strlen($token);
        if ($tokenLength > 8) {
            $maskedToken = substr($token, 0, 4) . '****' . substr($token, -4);
        } elseif ($tokenLength > 4) {
            $maskedToken = substr($token, 0, 2) . '****' . substr($token, -2);
        } else {
            $maskedToken = str_repeat('*', $tokenLength);
        }

        return sprintf(
            'Configuration(enabled=%s, service=%s, collector=%s, token=%s, tracingMode=%s, triggerTraceEnabled=%s, transactionName=%s, transactionSettings=%s)',
            $this->enabled ? 'true' : 'false',
            $this->service,
            $this->collector,
            $maskedToken,
            $this->tracingMode !== null ? ($this->tracingMode ? 'true' : 'false') : 'null',
            $this->triggerTraceEnabled ? 'true' : 'false',
            $this->transactionName !== null ? 'Closure' : 'null',
            json_encode($this->transactionSettings)
        );
    }
}
