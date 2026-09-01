<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Distribution;

use OpenTelemetry\SDK\Common\Distribution\DistributionConfiguration;

interface DistributionProperties
{
    /**
     * @template C of DistributionConfiguration
     * @param class-string<C> $distribution
     * @return C|null
     */
    public function getDistributionConfiguration(string $distribution): ?DistributionConfiguration;
}