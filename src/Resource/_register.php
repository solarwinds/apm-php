<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Resource\Detectors\Swo;

Registry::registerResourceDetector(KnownValues::VALUE_SWO, Swo::getInstance());
