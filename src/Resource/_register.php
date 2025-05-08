<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Resource\Detectors\K8s;
use Solarwinds\ApmPhp\Resource\Detectors\Swo;
use Solarwinds\ApmPhp\Resource\Detectors\Uams;

Registry::registerResourceDetector(KnownValues::VALUE_SWO, new Swo());
Registry::registerResourceDetector(KnownValues::VALUE_UAMS, new Uams());
Registry::registerResourceDetector(KnownValues::VALUE_K8S, new K8s());
