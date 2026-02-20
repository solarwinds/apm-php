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
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SemConv\ResourceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Configuration as SolarwindsConfiguration;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\Trace\Sampler\HttpSampler;
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;

class SwoSamplerFactory
{
    use LogsMessagesTrait;
    private const TRACEIDRATIO_PREFIX = 'traceidratio';
    private const SOLARWINDS_PREFIX = 'solarwinds';
    private const VALUE_SOLARWINDS_HTTP = 'solarwinds_http';
    private const VALUE_SOLARWINDS_JSON = 'solarwinds_json';
    private const DEFAULT_APM_COLLECTOR = 'apm.collector.na-01.cloud.solarwinds.com';
    private const SERVICE_KEY_DELIMITER = ':';
    private const SERVICE_KEY_PATTERN = '/^([^:]+):([^:]+)$/';
    private ResourceInfo $resource;

    public function __construct(?ResourceInfo $resource = null)
    {
        $this->resource = $resource ?? ResourceInfoFactory::defaultResource();
    }
    /**
     * Extracts and builds a SolarwindsConfiguration for HTTP or JSON samplers.
     * @param bool $isHttp True for HTTP, false for JSON
     * @param string|null $serviceKey Service key string for HTTP, null for JSON
     * @return SolarwindsConfiguration
     */
    public function getSolarwindsConfiguration(bool $isHttp, ?string $serviceKey = null): SolarwindsConfiguration
    {
        $collector = $isHttp
            ? (Configuration::has(SolarwindsEnv::SW_APM_COLLECTOR)
                ? Configuration::getString(SolarwindsEnv::SW_APM_COLLECTOR)
                : self::DEFAULT_APM_COLLECTOR)
            : '';
        $token = '';
        $service = 'unknown_service';
        if ($isHttp && $serviceKey) {
            [$token, $service] = explode(self::SERVICE_KEY_DELIMITER, $serviceKey);
        }
        $otelServiceName = Configuration::has(Env::OTEL_SERVICE_NAME) ? Configuration::getString(Env::OTEL_SERVICE_NAME) : null;
        $resourceAttributeServiceName = $this->resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME);
        $tracingMode = !Configuration::has(SolarwindsEnv::SW_APM_TRACING_MODE) || strtolower(Configuration::getString(SolarwindsEnv::SW_APM_TRACING_MODE)) === 'enabled';
        $triggerTraceEnabled = !Configuration::has(SolarwindsEnv::SW_APM_TRIGGER_TRACE) || strtolower(Configuration::getString(SolarwindsEnv::SW_APM_TRIGGER_TRACE)) === 'enabled';
        $transactionSettingsFile = Configuration::has(SolarwindsEnv::SW_APM_TRANSACTION_SETTINGS_FILE)
            ? Configuration::getString(SolarwindsEnv::SW_APM_TRANSACTION_SETTINGS_FILE)
            : null;
        $transactionSettingsStr = Configuration::has(SolarwindsEnv::SW_APM_TRANSACTION_SETTINGS)
            ? Configuration::getString(SolarwindsEnv::SW_APM_TRANSACTION_SETTINGS)
            : null;
        $transactionSettings = [];
        if ($transactionSettingsFile && file_exists($transactionSettingsFile)) {
            try {
                $transactionSettingsContent = file_get_contents($transactionSettingsFile);
                if ($transactionSettingsContent !== false) {
                    $transactionSettingsJson = json_decode($transactionSettingsContent, true);
                    if (is_array($transactionSettingsJson)) {
                        $transactionSettings = $transactionSettingsJson;
                    } else {
                        self::logWarning('Content of SW_APM_TRANSACTION_SETTINGS_FILE ' . $transactionSettingsFile . ' is not a valid JSON array. Falling back to SW_APM_TRANSACTION_SETTINGS environment variable or empty transaction settings.');
                    }
                } else {
                    self::logWarning('Unable to read SW_APM_TRANSACTION_SETTINGS_FILE ' . $transactionSettingsFile . ' . Falling back to SW_APM_TRANSACTION_SETTINGS environment variable or empty transaction settings.');
                }
            } catch (Exception $e) {
                self::logWarning('Error processing SW_APM_TRANSACTION_SETTINGS_FILE ' . $transactionSettingsFile . ' : ' . $e->getMessage() . '. Falling back to SW_APM_TRANSACTION_SETTINGS environment variable or empty transaction settings.');
            }
        } elseif ($transactionSettingsStr) {
            try {
                $transactionSettingsJson = json_decode($transactionSettingsStr, true);
                if (is_array($transactionSettingsJson)) {
                    $transactionSettings = $transactionSettingsJson;
                } else {
                    self::logWarning('SW_APM_TRANSACTION_SETTINGS is not a valid JSON array. Falling back to empty transaction settings.');
                }
            } catch (Exception $e) {
                self::logWarning('Error processing SW_APM_TRANSACTION_SETTINGS: ' . $e->getMessage() . '. Falling back to empty transaction settings.');
            }
        }

        return new SolarwindsConfiguration(
            service: $otelServiceName ?? $resourceAttributeServiceName ?? $service,
            collector: $isHttp ? 'https://' . $collector : '',
            token: $token,
            tracingMode: $tracingMode,
            triggerTraceEnabled: $triggerTraceEnabled,
            transactionSettings: $transactionSettings
        );
    }

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
                            $serviceKey = Configuration::has(SolarwindsEnv::SW_APM_SERVICE_KEY)
                                ? Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY)
                                : null;
                            if ($serviceKey && preg_match(self::SERVICE_KEY_PATTERN, $serviceKey)) {
                                $configuration = $this->getSolarwindsConfiguration(true, $serviceKey);
                                $http = new HttpSampler($meterProvider, $configuration);

                                return new ParentBased($http, $http, $http);
                            }
                            self::logWarning('SW_APM_SERVICE_KEY is not correctly formatted. Falling back to AlwaysOffSampler.');

                            return new AlwaysOffSampler();
                        } catch (Exception $e) {
                            self::logWarning('SW_APM_COLLECTOR/SW_APM_SERVICE_KEY ' . $e->getMessage() . '. Falling back to AlwaysOffSampler.');

                            return new AlwaysOffSampler();
                        }
                    }
                case self::VALUE_SOLARWINDS_JSON:
                    $configuration = $this->getSolarwindsConfiguration(false);
                    $json = new JsonSampler($meterProvider, $configuration);

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
