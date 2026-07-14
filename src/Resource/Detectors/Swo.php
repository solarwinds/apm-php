<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\Attributes\ServiceAttributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;

final class Swo implements ResourceDetectorInterface
{
    private const PACKAGIST_COMPOSER_NAME = 'solarwinds/apm';
    private const SERVICE_KEY_DELIMITER = ':';
    public const SW_DATA_MODULE = 'sw.data.module';
    public const SW_APM_VERSION = 'sw.apm.version';
    public const ENV_WEBSITE_OWNER_NAME_KEY = 'WEBSITE_OWNER_NAME';
    public const ENV_WEBSITE_RESOURCE_GROUP_KEY = 'WEBSITE_RESOURCE_GROUP';
    public const ENV_WEBSITE_SITE_NAME_KEY = 'WEBSITE_SITE_NAME';

    public function getResource(): ResourceInfo
    {
        $attributes = [
            self::SW_DATA_MODULE => 'apm',
            self::SW_APM_VERSION => InstalledVersions::getPrettyVersion(self::PACKAGIST_COMPOSER_NAME) ?? 'unknown',
        ];

        $serviceKey = Configuration::has(SolarwindsEnv::SW_APM_SERVICE_KEY)
            ? Configuration::getString(SolarwindsEnv::SW_APM_SERVICE_KEY)
            : null;

        // Check if user installed open-telemetry/detector-azure and it is running in an Azure app service
        $azureAppService = false;
        if (class_exists('OpenTelemetry\\Contrib\\Resource\\Detector\\Azure\\AppService\\Detector')) {
            $name = getenv(self::ENV_WEBSITE_SITE_NAME_KEY);
            $groupName = getenv(self::ENV_WEBSITE_RESOURCE_GROUP_KEY);
            $subscriptionId = getenv(self::ENV_WEBSITE_OWNER_NAME_KEY);
            if ($name && $groupName && $subscriptionId) {
                $azureAppService = true;
            }
        }

        // Azure app service has higher precedence than SW_APM_SERVICE_KEY
        if (!$azureAppService && $serviceKey && str_contains($serviceKey, self::SERVICE_KEY_DELIMITER)) {
            [, $swServiceName] = explode(self::SERVICE_KEY_DELIMITER, $serviceKey);
            if (strlen($swServiceName) > 0) {
                $attributes[ServiceAttributes::SERVICE_NAME] = $swServiceName;
            }
        }

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }
}
