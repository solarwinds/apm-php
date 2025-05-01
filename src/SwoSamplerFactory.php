<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use InvalidArgumentException;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Common\Configuration\KnownValues as Values;
use OpenTelemetry\SDK\Common\Configuration\Variables as Env;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;

use Solarwinds\ApmPhp\Configuration as SolarwindsConfiguration;
use Solarwinds\ApmPhp\Variables as SolarwindsEnv;

class SwoSamplerFactory
{
    private const TRACEIDRATIO_PREFIX = 'traceidratio';
    private const SOLARWINDS_PREFIX = 'solarwinds';
    private const VALUE_SOLARWINDS_HTTP = 'solarwinds_http';
    private const VALUE_SOLARWINDS_JSON = 'solarwinds_json';

    public function create(): SamplerInterface
    {
        $name = Configuration::getString(Env::OTEL_TRACES_SAMPLER);

        if (str_contains($name, self::TRACEIDRATIO_PREFIX) || str_contains($name, self::SOLARWINDS_PREFIX)) {
            $arg = Configuration::getRatio(Env::OTEL_TRACES_SAMPLER_ARG);

            switch ($name) {
                case Values::VALUE_TRACE_ID_RATIO:
                    return new TraceIdRatioBasedSampler($arg);
                case Values::VALUE_PARENT_BASED_TRACE_ID_RATIO:
                    return new ParentBased(new TraceIdRatioBasedSampler($arg));
                case self::VALUE_SOLARWINDS_HTTP:
                    $collector = Configuration::getString(SolarwindsEnv::SW_APM_COLLECTOR);
                    if (empty($collector)) {
                        $collector = 'apm.collector.na-01.cloud.solarwinds.com';
                    }
                    $serviceKey = Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY);
                    [$token, $service] = explode(':', $serviceKey);
                    $endpoint = 'https://' . $collector;
                    $http = new HttpSampler(null, new SolarwindsConfiguration(true, $service, $endpoint, ['Authorization: Bearer ' . $token,], true, true, null, []), null);
                    return new ParentBased($http, $http, $http);
                case self::VALUE_SOLARWINDS_JSON:
                    $path = Configuration::getString(SolarwindsEnv::SW_APM_SETTINGS_JSON_PATH);
                    if (empty($path)) {
                        $path = sys_get_temp_dir() . 'solarwinds-apm-settings.json';
                    }
                    $serviceKey = Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY);
                    [, $service] = explode(':', $serviceKey);
                    $json = new JsonSampler(null, new SolarwindsConfiguration(true, $service, '', [], true, true, null, []), $path);
                    return new ParentBased($json, $json, $json);
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
