# Configuration

## Specification
[solarwinds/apm](https://packagist.org/packages/solarwinds/apm) supports [OpenTelemetry SDK configuration](https://opentelemetry.io/docs/zero-code/php/#configuration), all available variables are listed [here](https://github.com/open-telemetry/opentelemetry-php/blob/main/src/SDK/Common/Configuration/Variables.php).

Additionally the following SolarWinds-specific configurations are supported:

| Configuration                    | Default                                  | Description                                                                                                                                      |
|----------------------------------|------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| SW_APM_SERVICE_KEY               |                                          | **Service key**. See [Service Name](#service-name)                                                                                               |
| SW_APM_COLLECTOR                 | apm.collector.na-01.cloud.solarwinds.com | [APM collector endpoint](https://documentation.solarwinds.com/en/success_center/observability/content/system_requirements/endpoints.htm#General) |
| SW_APM_TRACING_MODE              | enabled                                  | Enable observability for the service. Valid values: enabled, disabled                                                                            |
| SW_APM_TRIGGER_TRACE             | enabled                                  | Enable the trigger trace feature in solarwinds sampler. Valid values: enabled, disabled                                                          |
| SW_APM_TRANSACTION_NAME          |                                          | User defined transaction name for all requests                                                                                                   |
| SW_APM_TRANSACTION_SETTINGS      |                                          | Json string that defines the transaction settings. See [Transaction Settings](#transaction-settings)                                             |
| SW_APM_TRANSACTION_SETTINGS_FILE |                                          | Absolute path to a JSON file that defines the transaction settings. See [Transaction Settings](#transaction-settings)                            |
| SW_K8S_POD_NAMESPACE             |                                          | User defined k8s pod namespace for k8s resource detector                                                                                         |
| SW_K8S_POD_UID                   |                                          | User defined k8s pod uid for k8s resource detector                                                                                               |
| SW_K8S_POD_NAME                  |                                          | User defined k8s pod name for k8s resource detector                                                                                              |

### Service Name

By default, the service name portion of the service key is used, e.g. `my-service` if the service key is `SW_APM_SERVICE_KEY=api-token:my-service`. If the `OTEL_SERVICE_NAME` or `OTEL_RESOURCE_ATTRIBUTES` environment variable is used to specify a service name, it will take precedence over the default.

### Transaction Settings

Transaction settings allow you to filter specific transactions for sampling and trigger trace.
You can define these settings as a JSON array string, where each entry has the following format:

- **tracing**: `"enabled"` or `"disabled"` — indicates whether the transaction should be traced when the regex matches.
- **regex**: value should be a PHP regular expression (see [pcre](https://www.php.net/manual/en/book.pcre.php) specification). Since the regular expression is defined in a JSON string, double backslashes are needed for PHP regex escapes. The first backslash ensures the second backslash is retained as a PHP escape character, for example, the JSON string `\\.` would become the regular expression `\.` that matches on a literal `.` instead of any character.

**How matching works:**
- Each entry is compared against the URL (e.g., `scheme://host/request_uri`) or the [span kind](https://github.com/open-telemetry/opentelemetry-specification/blob/v1.6.1/specification/trace/api.md#spankind) and span name (concatenated as `INTERNAL:span-name`).
- Entries are applied in the order they appear in the JSON array.
- If multiple entries match, the first one is used.

**Example JSON array string:**

Below is an example that disables tracing for all checkout page requests, and disables tracing for all `.css` requests:

```json
[
  {
    "tracing": "disabled",
    "regex": "/^.*\\/checkout\\/.*$/"
  },
  {
    "tracing": "disabled",
    "regex": "/^.*\\.css$/"
  }
]
```
Another example to disable tracing for url `http://my.domain.com/foo`:
```json
[
  {
    "tracing": "disabled",
    "regex": "/^http:\\/\\/my.domain.com\\/foo$/"
  }
]
```
or use other regex delimiter, e.g. `#`
```json
[
  {
    "tracing": "disabled",
    "regex": "#^http://my.domain.com/foo$#"
  }
]
```

You can provide the JSON array string in a file and set its path with the `SW_APM_TRANSACTION_SETTINGS_FILE` environment variable, or set it directly with the `SW_APM_TRANSACTION_SETTINGS` environment variable.

> **Note:**
> If both `SW_APM_TRANSACTION_SETTINGS_FILE` and `SW_APM_TRANSACTION_SETTINGS` are set, the file takes precedence.

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