<?php

declare(strict_types=1);

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;

putenv('OTEL_SERVICE_NAME=apm-php-basic-test-service');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=console');
putenv('OTEL_METRICS_EXPORTER=console');
putenv('OTEL_LOGS_EXPORTER=console');
putenv('OTEL_LOG_LEVEL=info');
putenv('OTEL_TRACES_SAMPLER=solarwinds_http');
putenv('OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE=delta');
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

$child1 = $tracer->spanBuilder('GET /posts/:id')
    ->setSpanKind(SpanKind::KIND_SERVER)
    ->startSpan();
$child1Scope = $child1->activate();
$child1->setAttribute('http.method"', 'GET');
$child1->setAttribute('http.route"', '/posts/:id');
$child1->setAttribute('http.url', 'http://localhost:8080/posts/123');
$child1->setAttribute('http.status_code', 200);

try {
    throw new Exception('Some error message');
} catch (Exception $exc) {
    $child1->setStatus('Error', $exc->getMessage());
    $child1->recordException($exc);
}
$child1Scope->detach();
$child1->end();

$child2 = $tracer->spanBuilder('child2-of-main')->startSpan();
$child2Scope = $child2->activate();
$child2->setAttributes([
    'db.system' => 'mysql',
    'db.statement' => 'SELECT * FROM posts LIMIT 100',
]);
$child2Scope->detach();
$child2->end();

// End the span and detached context when the operation we are measuring is done.
$mainScope->detach();
$main->end();

echo 'Finished!' . PHP_EOL;
