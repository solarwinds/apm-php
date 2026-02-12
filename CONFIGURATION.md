# Configuration

## Specification
[solarwinds/apm](https://packagist.org/packages/solarwinds/apm) supports [OpenTelemetry SDK configuration](https://opentelemetry.io/docs/zero-code/php/#configuration).

In addition to the [opentelemetry-php configuration](https://github.com/open-telemetry/opentelemetry-php/blob/main/src/SDK/Common/Configuration/Variables.php), the following configurations are supported:

| Configuration                | Default                                  | Description                                                                                                                                      |
|------------------------------|------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| SW_APM_SERVICE_KEY           |                                          | **Service key**. See [Service Name](#service-name)                                                                                               |
| SW_APM_COLLECTOR             | apm.collector.na-01.cloud.solarwinds.com | [APM collector endpoint](https://documentation.solarwinds.com/en/success_center/observability/content/system_requirements/endpoints.htm#General) |
| SW_APM_TRACING_MODE          | true                                     | Enable observability for the service                                                                                                             |
| SW_APM_TRIGGER_TRACE_ENABLED | true                                     | Enable the trigger trace feature in solarwinds sampler                                                                                           |
| SW_APM_TRANSACTION_NAME      |                                          | User defined transaction name for all requests                                                                                                   |
| SW_APM_TRANSACTION_SETTINGS  |                                          | Json string to define the transaction settings. See [Transaction Settings](#transaction-settings)                                                |
| SW_K8S_POD_NAMESPACE         |                                          | User defined k8s pod namespace for k8s resource detector                                                                                         |
| SW_K8S_POD_UID               |                                          | User defined k8s pod uid for k8s resource detector                                                                                               |
| SW_K8S_POD_NAME              |                                          | User defined k8s pod name for k8s resource detector                                                                                              |

### Service Name

By default, the service name portion of the service key is used, e.g. `my-service` if the service key is `SW_APM_SERVICE_KEY=api-token:my-service`. If the `OTEL_SERVICE_NAME` or `OTEL_RESOURCE_ATTRIBUTES` environment variable is used to specify a service name, it will take precedence over the default.

### Transaction Settings

Transaction settings is to filter specific transactions for sampling and trigger trace. It is a JSON string with the following format:

Config value:
- `tracing`: `enabled` or `disabled` to indicate whether the transaction should be traced when the regex matches.
- `regex`: a regular expression to match the transaction name.

```json
[
  {
    "tracing": "enabled",
    "regex": "/^.*$/"
  },
  {
    "tracing": "disabled",
    "regex": "/^http:\\/\\/localhost\\/test$/"
  }
]
```

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

### Automatic configuration
`solarwinds/apm` uses OpenTelemetry SDK autoloading with `OTEL_PHP_AUTOLOAD_ENABLED=true`, you can retrieve the global logger provider. That may be a no-op implementation if there was any misconfiguration.

Retrieve the Globals LoggerProvider and pass it to the handler:
```php
$handler = new Handler(
    OpenTelemetry\API\Globals::loggerProvider(),
    LogLevel::INFO, //or `Logger::INFO`, or `Level::Info` depending on monolog version
    true,
);
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

If the application log is not exported via OTLP, trace context can be injected into the log messages by using the OTel SDK function `Context::getCurrent()`.

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