<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace;

use InvalidArgumentException;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Common\Configuration\KnownValues as Values;
use OpenTelemetry\SDK\Common\Configuration\Variables as Env;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration as SolarwindsConfiguration;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\Trace\Sampler\HttpSampler;

class SwoSamplerFactory
{
    private const TRACEIDRATIO_PREFIX = 'traceidratio';
    private const SOLARWINDS_PREFIX = 'solarwinds';
    private const VALUE_SOLARWINDS_HTTP = 'solarwinds_http';
    private const DEFAULT_APM_COLLECTOR = 'apm.collector.na-01.cloud.solarwinds.com';
    private const DUMMY_SERVICE_KEY = 'your-token:default-service';

    public function create(?MeterProviderInterface $meterProvider = null): SamplerInterface
    {
        $name = Configuration::getString(Env::OTEL_TRACES_SAMPLER);

        if (str_contains($name, self::TRACEIDRATIO_PREFIX) || str_contains($name, self::SOLARWINDS_PREFIX)) {
            switch ($name) {
                case Values::VALUE_TRACE_ID_RATIO:
                    $arg = Configuration::getRatio(Env::OTEL_TRACES_SAMPLER_ARG);

                    return new TraceIdRatioBasedSampler($arg);
                case Values::VALUE_PARENT_BASED_TRACE_ID_RATIO:
                    $arg = Configuration::getRatio(Env::OTEL_TRACES_SAMPLER_ARG);

                    return new ParentBased(new TraceIdRatioBasedSampler($arg));
                case self::VALUE_SOLARWINDS_HTTP:
                    $collector = Configuration::getString(SolarwindsEnv::SW_APM_COLLECTOR, self::DEFAULT_APM_COLLECTOR);
                    $serviceKey = Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY, self::DUMMY_SERVICE_KEY);
                    [$token, $service] = explode(':', $serviceKey);
                    $http = new HttpSampler($meterProvider, new SolarwindsConfiguration(true, $service, 'https://' . $collector, ['Authorization' => 'Bearer ' . $token], true, true, null, []), null);

                    return new ParentBased($http, $http, $http);
            }
        }

        return match ($name) {
            Values::VALUE_ALWAYS_ON => new AlwaysOnSampler(),
            Values::VALUE_ALWAYS_OFF => new AlwaysOffSampler(),
            Values::VALUE_PARENT_BASED_ALWAYS_ON => new ParentBased(new AlwaysOnSampler()),
            Values::VALUE_PARENT_BASED_ALWAYS_OFF => new ParentBased(new AlwaysOffSampler()),
            default => throw new InvalidArgumentException(sprintf('Unknown sampler: %s', $name)),
        };
    }
}
