<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\After;

trait TestState
{
    private array $environmentVariables = [];

    #[After]
    protected function restoreEnvironmentVariables(): void
    {
        foreach ($this->environmentVariables as $variable => $value) {
            putenv(false === $value ? $variable : "{$variable}={$value}");
        }
    }

    protected function setEnvironmentVariable(string $variable, mixed $value): void
    {
        if (!isset($this->environmentVariables[$variable])) {
            $this->environmentVariables[$variable] = getenv($variable);
        }

        putenv(null === $value ? $variable : "{$variable}={$value}");
    }
}
