<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Propagator\SwoTraceState\SwoTraceStatePropagator;

Registry::registerTextMapPropagator(KnownValues::VALUE_SWOTRACESTATE, SwoTraceStatePropagator::getInstance());
