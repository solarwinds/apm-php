<?php

declare(strict_types=1);

namespace OpenTelemetry\Example;

use OpenTelemetry\API\Globals;

putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=console');
putenv('OTEL_METRICS_EXPORTER=console');
putenv('OTEL_LOGS_EXPORTER=console');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=grpc');
//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://collector:4317');
putenv('OTEL_LOG_LEVEL=info');
putenv('OTEL_TRACES_SAMPLER=solarwinds_http');
putenv('OTEL_TRACES_SAMPLER_ARG=0.1');
putenv('SW_APM_COLLECTOR=apm.collector.na-01.cloud.solarwinds.com');
putenv('SW_APM_SERVICE_KEY=');
putenv('OTEL_PROPAGATORS=baggage,tracecontext');

echo 'autoloading SDK example starting...' . PHP_EOL;

// Composer autoloader will execute SDK/_autoload.php which will register global instrumentation from environment configuration
// require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';

$tracer = Globals::tracerProvider()->getTracer('example');
$meter = Globals::meterProvider()->getMeter('example');

//$instrumentation = new \OpenTelemetry\API\Instrumentation\CachedInstrumentation('demo');
//
//$instrumentation->tracer()->spanBuilder('root')->startSpan()->end();
//$instrumentation->meter()->createCounter('cnt')->add(1);
//$instrumentation->eventLogger()->emit('foo', 'hello, otel');

echo 'Finished!' . PHP_EOL;
