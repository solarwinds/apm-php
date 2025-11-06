<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SDK\Common\Configuration\Variables;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\Resource\Detectors\Swo;

#[CoversClass(Swo::class)]
class SwoTest extends TestCase
{
    public function test_swo_get_resource_from_otel_service_name(): void
    {
        putenv(Variables::OTEL_SERVICE_NAME . '=otel_service_name');
        $resourceDetector = new swo();
        $resource = $resourceDetector->getResource();
        $name = 'solarwinds/apm';
        $version = InstalledVersions::getPrettyVersion($name);

        $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
        $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertSame('otel_service_name', $resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
        putenv(Variables::OTEL_SERVICE_NAME);
    }

    public function test_swo_get_resource_service_name_from_sw_apm_service_key(): void
    {
        $serviceKey = getenv(SolarwindsEnv::SW_APM_SERVICE_KEY);
        if (empty($serviceKey) || !str_contains($serviceKey, ':')) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set or invalid.');
        }
        [, $service] = explode(':', $serviceKey);
        $resourceDetector = new swo();
        $resource = $resourceDetector->getResource();
        $name = 'solarwinds/apm';
        $version = InstalledVersions::getPrettyVersion($name);

        $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
        $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertSame($service, $resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
    }

    public function test_swo_detector(): void
    {
        $resource = (new swo())->getResource();

        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertNotNull($resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
    }

}
