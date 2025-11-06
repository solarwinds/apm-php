<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Common\Configuration\Variables;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;

final class Swo implements ResourceDetectorInterface
{
    private const PACKAGIST_COMPOSER_NAME = 'solarwinds/apm';
    private const SERVICE_KEY_DELIMITER = ':';
    public const SW_DATA_MODULE = 'sw.data.module';
    public const SW_APM_VERSION = 'sw.apm.version';

    public function getResource(): ResourceInfo
    {
        $otelServiceName = Configuration::has(Variables::OTEL_SERVICE_NAME)
            ? Configuration::getString(Variables::OTEL_SERVICE_NAME)
            : null;
        $serviceKey = Configuration::has(SolarwindsEnv::SW_APM_SERVICE_KEY)
            ? Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY)
            : null;
        $swServiceName = null;
        if ($serviceKey && str_contains($serviceKey, self::SERVICE_KEY_DELIMITER)) {
            [, $swServiceName] = explode(self::SERVICE_KEY_DELIMITER, $serviceKey);
        }

        $attributes = [
            self::SW_DATA_MODULE => 'apm',
            self::SW_APM_VERSION => InstalledVersions::getPrettyVersion(self::PACKAGIST_COMPOSER_NAME) ?? 'unknown',
            // OTEL_SERVICE_NAME takes precedence over $service part of SW_APM_SERVICE_KEY
            ResourceAttributes::SERVICE_NAME => $otelServiceName ?? $swServiceName ?? 'unknown_service',
        ];

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }
}
