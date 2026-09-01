<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Instrumentation;

use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;

class SolarwindsConfig implements GeneralInstrumentationConfiguration
{
    public function __construct(public readonly array $config)
    {
    }
}