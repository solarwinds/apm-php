<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use function class_exists;
use Composer\InstalledVersions;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;

final class Swo implements ResourceDetectorInterface
{
    public const SW_DATA_MODULE = 'sw.data.module';
    public const SW_APM_VERSION = 'sw.apm.version';
    private static ?self $instance = null;
    public function getResource(): ResourceInfo
    {
        if (!class_exists(InstalledVersions::class)) {
            return ResourceInfoFactory::emptyResource();
        }

        $attributes = [
            self::SW_DATA_MODULE => 'apm',
            self::SW_APM_VERSION => InstalledVersions::getRootPackage()['pretty_version'],
        ];

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
