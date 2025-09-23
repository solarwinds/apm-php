<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Registry;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\ResponsePropagator\XTraceOptionsResponse\XTraceOptionsResponsePropagator;

Registry::registerResponsePropagator(KnownValues::VALUE_XTRACEOPTIONSRESPONSE, XTraceOptionsResponsePropagator::getInstance());
