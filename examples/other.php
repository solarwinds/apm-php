<?php

declare(strict_types=1);

namespace OpenTelemetry\Example;

putenv('OTEL_SERVICE_NAME=apm-php-other-test-service');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=console');
putenv('OTEL_METRICS_EXPORTER=console');
putenv('OTEL_LOGS_EXPORTER=console');
putenv('OTEL_LOG_LEVEL=info');
putenv('OTEL_TRACES_SAMPLER=solarwinds_http');
putenv('SW_APM_COLLECTOR=apm.collector.na-01.cloud.solarwinds.com');
putenv('SW_APM_SERVICE_KEY=token:apm-php-other');
putenv('SW_APM_TRANSACTION_NAME=txn');
putenv('SW_APM_TRANSACTION_SETTINGS=[{"tracing":"enabled", "regex":"/^.*$/"}, {"tracing": "disabled", "regex":"/^abc$/"}]');
putenv('OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions');

echo 'autoloading SDK example starting...' . PHP_EOL;

// Composer autoloader will execute SDK/_autoload.php which will register global instrumentation from environment configuration
require __DIR__ . '/../vendor/autoload.php';

$instrumentation = new \OpenTelemetry\API\Instrumentation\CachedInstrumentation('demo');

$instrumentation->tracer()->spanBuilder('root')->startSpan()->end();
$instrumentation->meter()->createCounter('cnt')->add(1);
$instrumentation->eventLogger()->emit('foo', 'hello, otel');

echo 'Finished!' . PHP_EOL;
