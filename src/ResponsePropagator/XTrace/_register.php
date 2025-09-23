<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\ResponsePropagator\XTrace\XTraceResponsePropagator;

Registry::registerResponsePropagator(KnownValues::VALUE_XTRACE, XTraceResponsePropagator::getInstance());
