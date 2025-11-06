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
    /**
     * This method is called before each test.
     * @codeCoverageIgnore
     */
    private string|array|false $backup;
    protected function setUp(): void
    {
        $this->backup = getenv(SolarwindsEnv::SW_APM_SERVICE_KEY);
        putenv(SolarwindsEnv::SW_APM_SERVICE_KEY);
    }
    /**
     * This method is called after each test.
     * @codeCoverageIgnore
     */
    protected function tearDown(): void
    {
        if ($this->backup !== false && is_string($this->backup)) {
            putenv(SolarwindsEnv::SW_APM_SERVICE_KEY . '=' . $this->backup);
        } else {
            putenv(SolarwindsEnv::SW_APM_SERVICE_KEY);
        }
    }

    public function test_swo_get_resource_from_otel_service_name(): void
    {
        putenv(Variables::OTEL_SERVICE_NAME . '=otel_service_name');
        putenv(SolarwindsEnv::SW_APM_SERVICE_KEY . '=token:sw_apm_service');
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
        putenv(SolarwindsEnv::SW_APM_SERVICE_KEY . '=token:sw_apm_service');
        $resourceDetector = new swo();
        $resource = $resourceDetector->getResource();
        $name = 'solarwinds/apm';
        $version = InstalledVersions::getPrettyVersion($name);

        $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
        $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertSame('sw_apm_service', $resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
    }

    public function test_swo_get_resource_service_name_from_mandatory_default(): void
    {
        $resourceDetector = new swo();
        $resource = $resourceDetector->getResource();
        $name = 'solarwinds/apm';
        $version = InstalledVersions::getPrettyVersion($name);

        $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
        $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertSame('unknown_service', $resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));

    }

    public function test_swo_detector(): void
    {
        $resource = (new swo())->getResource();

        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_APM_VERSION));
        $this->assertNotNull($resource->getAttributes()->get(ResourceAttributes::SERVICE_NAME));
    }

}
