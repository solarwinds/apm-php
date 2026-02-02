<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace;

use Exception;
use InvalidArgumentException;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
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
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;
use Solarwinds\ApmPhp\Trace\Sampler\ParentBasedSampler;

class SwoSamplerFactory
{
    use LogsMessagesTrait;
    private const TRACEIDRATIO_PREFIX = 'traceidratio';
    private const SOLARWINDS_PREFIX = 'solarwinds';
    private const VALUE_SOLARWINDS_HTTP = 'solarwinds_http';
    private const VALUE_SOLARWINDS_JSON = 'solarwinds_json';
    private const DEFAULT_APM_COLLECTOR = 'apm.collector.na-01.cloud.solarwinds.com';
    private const SERVICE_KEY_DELIMITER = ':';

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
                    {
                        try {
                            $collector = Configuration::has(SolarwindsEnv::SW_APM_COLLECTOR)
                                ? Configuration::getString(SolarwindsEnv::SW_APM_COLLECTOR)
                                : self::DEFAULT_APM_COLLECTOR;
                            $serviceKey = Configuration::has(SolarwindsEnv::SW_APM_SERVICE_KEY)
                                ? Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY)
                                : null;
                            if ($serviceKey && str_contains($serviceKey, self::SERVICE_KEY_DELIMITER)) {
                                [$token, $service] = explode(self::SERVICE_KEY_DELIMITER, $serviceKey);
                                $otelServiceName = Configuration::has(Env::OTEL_SERVICE_NAME) ? Configuration::getString(Env::OTEL_SERVICE_NAME) : null;
                                // OTEL_SERVICE_NAME takes precedence over $service part of SW_APM_SERVICE_KEY
                                $http = new HttpSampler($meterProvider, new SolarwindsConfiguration(true, $otelServiceName ?? $service, 'https://' . $collector, $token, [], true, true, null, []), null);

                                return new ParentBasedSampler($http, $http, $http);
                            }
                            self::logWarning('SW_APM_SERVICE_KEY is not correctly formatted. Falling back to AlwaysOffSampler.');

                            return new AlwaysOffSampler();
                        } catch (Exception $e) {
                            self::logWarning('SW_APM_COLLECTOR/SW_APM_SERVICE_KEY ' . $e->getMessage() . '. Falling back to AlwaysOffSampler.');

                            return new AlwaysOffSampler();
                        }
                    }
                case self::VALUE_SOLARWINDS_JSON:
                    $json = new JsonSampler($meterProvider, new SolarwindsConfiguration(true, '', '', '', [], true, true, null, []));

                    return new ParentBasedSampler($json, $json, $json);
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
