# Configuration

## Trace Context in Database Queries

Install [OpenTelemetry SQL Commenter](https://packagist.org/packages/open-telemetry/opentelemetry-sqlcommenter) to propagate trace context to database queries:
```bash
composer require open-telemetry/opentelemetry-sqlcommenter
```
Supported DB libraries:
- open-telemetry/opentelemetry-auto-mysqli
- open-telemetry/opentelemetry-auto-pdo
- open-telemetry/opentelemetry-auto-postgresql

Enable context propagation:
```bash
OTEL_PHP_SQLCOMMENTER_CONTEXT_PROPAGATORS=tracecontext
```
Enable SQL comment attributes:
```bash
OTEL_PHP_SQLCOMMENTER_ATTRIBUTE=true
```

## Exporting Application Logs

Install [OpenTelemetry Monolog logger](https://packagist.org/packages/open-telemetry/opentelemetry-logger-monolog) and follow its [usage](https://github.com/opentelemetry-php/contrib-logger-monolog?tab=readme-ov-file#usage) to export application logs for [monolog/monolog](https://packagist.org/packages/monolog/monolog).
```bash
composer require open-telemetry/opentelemetry-logger-monolog
```

## Trace Context in Logs

When [Exporting Application Logs](#exporting-application-logs)
`service.name`, `traceId`, `spanId` & `flags` will be automatically added to the OpenTelemetry logs.

Example log output:
```json
{
  ...
  "resource":{"attributes":[{"key":"service.name","value":{"stringValue":"php-example"}}]},
  ...
  "logRecords":[{"timeUnixNano":"1762572315653380000","observedTimeUnixNano":"1762572315653559040","severityNumber":9,"severityText":"INFO","body":{"stringValue":"hello, otel"},"flags":1,"traceId":"1c52067371dfddaa6ed58c42d43d0a2f","spanId":"f33aaf87fc3ca8ab"}],
  ...
}
```

To add trace context to log messages, use OTEL SDK function `Context::getCurrent()`.

Monolog example capturing the trace context with a [processor](https://seldaek.github.io/monolog/doc/01-usage.html#using-processors):
```php
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;

$logger->pushProcessor(function ($record) {
  $spanContext = Span::fromContext(Context::getCurrent())->getContext();
  $record['message'] .= ' trace_id='.$spanContext->getTraceId() . ' span_id=' . $spanContext->getSpanId() . ' trace_flags=' . ($spanContext->getTraceFlags() ? '01' : '00');
  return $record;
});
```