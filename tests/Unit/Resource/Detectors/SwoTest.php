<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\Resource\Detectors\Swo;
use Solarwinds\ApmPhp\Tests\Unit\TestState;

#[CoversClass(Swo::class)]
class SwoTest extends TestCase
{
    use TestState;

    private const AZURE_APP_SERVICE_DETECTOR_CLASS = 'OpenTelemetry\\Contrib\\Resource\\Detector\\Azure\\AppService\\Detector';

    public function test_swo_get_resource_service_name_from_sw_apm_service_key(): void
    {
        $serviceKey = getenv(SolarwindsEnv::SW_APM_SERVICE_KEY);
        if ($serviceKey && str_contains($serviceKey, ':')) {
            [, $service] = explode(':', $serviceKey);
            $resourceDetector = new swo();
            $resource = $resourceDetector->getResource();
            $name = 'solarwinds/apm';
            $version = InstalledVersions::getPrettyVersion($name);

            $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
            $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
            $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
            $this->assertSame($service, $resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
        } else {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set or invalid.');
        }
    }

    public function test_swo_get_resource_does_not_use_service_name_from_service_key_on_azure_app_service(): void
    {
        if (!class_exists(self::AZURE_APP_SERVICE_DETECTOR_CLASS, false)) {
            class_alias(SwoTestAzureAppServiceDetector::class, self::AZURE_APP_SERVICE_DETECTOR_CLASS);
        }

        $this->setEnvironmentVariable(SolarwindsEnv::SW_APM_SERVICE_KEY, 'token:service-from-sw-apm-service-key');
        $this->setEnvironmentVariable(Swo::ENV_WEBSITE_SITE_NAME_KEY, 'app-name');
        $this->setEnvironmentVariable(Swo::ENV_WEBSITE_RESOURCE_GROUP_KEY, 'resource-group');
        $this->setEnvironmentVariable(Swo::ENV_WEBSITE_OWNER_NAME_KEY, 'subscription-id');

        $resource = (new Swo())->getResource();

        $this->assertNull($resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
    }

    public function test_swo_detector(): void
    {
        $resource = (new swo())->getResource();

        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_APM_VERSION));
    }
}

class SwoTestAzureAppServiceDetector
{
}
