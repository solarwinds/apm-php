<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsPropagator;

Registry::registerTextMapPropagator(KnownValues::VALUE_XTRACEOPTIONS, XTraceOptionsPropagator::getInstance());
