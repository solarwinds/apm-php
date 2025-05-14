<?php

declare(strict_types=1);

use OpenTelemetry\API\Globals;
use Solarwinds\ApmPhp\API\TransactionName;

putenv('OTEL_SERVICE_NAME="apm-php-basic-test-service"');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=console');
putenv('OTEL_METRICS_EXPORTER=console');
putenv('OTEL_LOGS_EXPORTER=console');
putenv('OTEL_LOG_LEVEL=none');
putenv('OTEL_TRACES_SAMPLER=solarwinds_http');
putenv('SW_APM_COLLECTOR=apm.collector.na-01.cloud.solarwinds.com');
putenv('SW_APM_SERVICE_KEY=token:apm-php-basic');
putenv('OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions');

require __DIR__ . '/../vendor/autoload.php';

// Create a tracer. Usually, tracer is a global variable.
$tracer = Globals::tracerProvider()->getTracer('app_or_package_name');
// Create a root span (a trace) to measure some operation.
$main = $tracer->spanBuilder('main-operation')->startSpan();
// Future spans will be parented to the currently active span.
$mainScope = $main->activate();
// Set transaction name after activating the span. Otherwise, we are unable to retrieve local root span (or root-most active span)
TransactionName::set('custom-transaction-name');
// End the span and detached context when the operation we are measuring is done.
$mainScope->detach();
$main->end();

echo 'Finished!' . PHP_EOL;
