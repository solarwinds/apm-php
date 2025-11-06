<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Composer\InstalledVersions;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;

final class Swo implements ResourceDetectorInterface
{
    private const PACKAGIST_COMPOSER_NAME = 'solarwinds/apm';
    public const SW_DATA_MODULE = 'sw.data.module';
    public const SW_APM_VERSION = 'sw.apm.version';
    public function getResource(): ResourceInfo
    {
        $attributes = [
            self::SW_DATA_MODULE => 'apm',
            self::SW_APM_VERSION => InstalledVersions::getPrettyVersion(self::PACKAGIST_COMPOSER_NAME) ?? 'unknown',
        ];

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }
}
