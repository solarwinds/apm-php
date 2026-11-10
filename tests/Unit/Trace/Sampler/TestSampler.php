<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\Sampler;
use Solarwinds\ApmPhp\Trace\Sampler\Settings;

class TestSampler extends Sampler
{
    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, mixed $settings)
    {
        parent::__construct($meterProvider, $config, $settings);
    }
    public function exposeParsedAndUpdateSettings(mixed $settings): ?Settings
    {
        return $this->parsedAndUpdateSettings($settings);
    }
}
