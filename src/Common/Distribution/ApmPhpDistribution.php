<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Distribution;

use OpenTelemetry\SDK\Common\Distribution\DistributionConfiguration;

final class ApmPhpDistribution implements DistributionConfiguration
{
    public function __construct(
        public readonly string $collector = "collector",
    ) {
    }
}