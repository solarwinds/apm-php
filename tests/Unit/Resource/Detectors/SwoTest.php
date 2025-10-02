<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Resource\Detectors\Swo;

#[CoversClass(Swo::class)]
class SwoTest extends TestCase
{
    public function test_swo_get_resource(): void
    {
        $resourceDetector = new swo();
        $resource = $resourceDetector->getResource();
        $name = 'solarwinds/apm';
        $version = InstalledVersions::getPrettyVersion($name);

        $this->assertSame(ResourceAttributes::SCHEMA_URL, $resource->getSchemaUrl());
        $this->assertSame('apm', $resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertSame($version, $resource->getAttributes()->get(Swo::SW_APM_VERSION));
    }

    public function test_swo_detector(): void
    {
        $resource = (new swo())->getResource();

        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_DATA_MODULE));
        $this->assertNotNull($resource->getAttributes()->get(Swo::SW_APM_VERSION));
    }

}
