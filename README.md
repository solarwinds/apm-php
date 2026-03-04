# SolarWinds APM PHP Library

![Packagist Version](https://img.shields.io/packagist/v/solarwinds/apm)
![Packagist Downloads](https://img.shields.io/packagist/dm/solarwinds/apm)
[![PHP CI](https://github.com/solarwinds/apm-php/actions/workflows/php.yml/badge.svg)](https://github.com/solarwinds/apm-php/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/solarwinds/apm-php/graph/badge.svg?token=g4IzcxmTSG)](https://codecov.io/github/solarwinds/apm-php)
[![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/solarwinds/apm-php/badge)](https://scorecard.dev/viewer/?uri=github.com/solarwinds/apm-php)
![GitHub License](https://img.shields.io/github/license/solarwinds/apm-php)

---

SolarWinds APM PHP is an OpenTelemetry-based library for distributed tracing and observability in PHP applications. It provides automatic and manual instrumentation, seamless integration with the SolarWinds Observability platform, and supports modern PHP frameworks.

## Prerequisites

Before you begin, ensure you have:
- [PHP 8.1+](https://www.php.net/)
- [PECL](https://pecl.php.net/)
- [Composer](https://getcomposer.org/)
- [pie](https://github.com/php/pie)

Check your versions:
```bash
php -v
composer -v
```

## Installation

Install the SolarWinds APM library:
```bash
composer require solarwinds/apm
```

Install a PSR-compatible HTTP client (required for OTLP exporter):
```bash
composer require guzzlehttp/guzzle
```

Set your service key (via environment variable):
```bash
export SW_APM_SERVICE_KEY=<your-service-key>
```

Set the [APM collector endpoint](https://documentation.solarwinds.com/en/success_center/observability/content/system_requirements/endpoints.htm?#Find) which provides sampling settings, default is `apm.collector.na-01.cloud.solarwinds.com`. Also note down the OTLP ingestion endpoint that corresponds to your tenant, e.g. `otel.collector.na-01.cloud.solarwinds.com:443`. By default, telemetry exports to a [local OTLP endpoint](https://opentelemetry.io/docs/languages/sdk-configuration/otlp-exporter/#otel_exporter_otlp_endpoint), see the example section below on exporting directly or through a local OpenTelemetry Collector to SolarWinds Observability.
```bash
export SW_APM_COLLECTOR=<your-apm-collector-url>
```

Install [solarwinds/apm_ext](https://packagist.org/packages/solarwinds/apm_ext) which caches sampling settings to reduce request latency:
```bash
pie install solarwinds/apm_ext
```

To [minimize export delays](https://opentelemetry.io/docs/languages/php/exporters/#minimizing-export-delays), opentelemetry-php recommends an [agent](https://opentelemetry.io/docs/collector/deploy/agent/) collector to receive the telemetry. We recommend using the [SolarWinds OpenTelemetry Collector](https://github.com/solarwinds/solarwinds-otel-collector-releases) for better integration with SolarWinds Observability. Please refer to [Solarwinds OpenTelemetry Collector documentation](https://documentation.solarwinds.com/en/success_center/observability/content/intro/otel/otel-collector.htm) for install instructions, and the example section below on configuring it to receive telemetry from the PHP application and export to SolarWinds Observability.

## Example Application

This section demonstrates automatic instrumentation using Slim and SolarWinds APM.

### 1. Create a minimal Slim app

```bash
composer init --no-interaction --require slim/slim:"^4" --require slim/psr7:"^1"
composer update
```

Create `index.php`:
```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/rolldice', function (Request $request, Response $response) {
    $result = random_int(1,6);
    $response->getBody()->write(strval($result));
    return $response;
});

$app->run();
```

Run the app:
```bash
php -S localhost:8080
```
Visit [http://localhost:8080/rolldice](http://localhost:8080/rolldice) to test.

### 2. Add zero-code instrumentation

Install the OpenTelemetry PHP extension ([instructions](https://opentelemetry.io/docs/zero-code/php/#install-the-opentelemetry-extension)) and verify:
```bash
php --ri opentelemetry
```

Add SolarWinds APM Library, the OTLP exporter dependency, and the Slim instrumentation. More instrumentation libraries can be found [here](https://packagist.org/packages/open-telemetry/?query=open-telemetry%2Fopentelemetry-):
```bash
composer config allow-plugins.php-http/discovery false
composer require solarwinds/apm guzzlehttp/guzzle open-telemetry/opentelemetry-auto-slim
```

### 3. Run with tracing enabled

Get your `<solarwinds-api-token>` from [SolarWinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration), and find the [OTLP ingestion endpoint](https://documentation.solarwinds.com/en/success_center/observability/content/system_requirements/endpoints.htm?#Find) that corresponds to your tenant, e.g. `otel.collector.na-01.cloud.solarwinds.com:443`.

Start the app with tracing, make sure to replace `<solarwinds-otlp-endpoint>` and `<solarwinds-api-token>` with your actual value:
```bash
env OTEL_PHP_AUTOLOAD_ENABLED=true \
    OTEL_SERVICE_NAME=php-example \
    OTEL_TRACES_SAMPLER=solarwinds_http \
    OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions \
    OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS=xtrace,xtraceoptionsresponse \
    OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE=delta \
    OTEL_EXPORTER_OTLP_ENDPOINT=<solarwinds-otlp-endpoint> \
    OTEL_EXPORTER_OTLP_HEADERS="Authorization=Bearer <solarwinds-api-token>" \
    SW_APM_SERVICE_KEY=<solarwinds-api-token>:php-example \
    OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION=base2_exponential_bucket_histogram \
    OTEL_EXPORTER_OTLP_ENDPOINT=https://otel.collector.na-01.cloud.solarwinds.com:443 \
    OTEL_EXPORTER_OTLP_HEADERS="Authorization=Bearer <token>" \
    SW_APM_SERVICE_KEY=<token>:php-example \
    php -S localhost:8080
```

Reload [http://localhost:8080/rolldice](http://localhost:8080/rolldice) and make several requests, the Service and its telemetry will show up in SolarWinds Observability within a few minutes. We have automatic tracing with zero code change! Let's continue to install the following to optimize performance and reduce latency.

### 4. Install solarwinds/apm_ext extension

```bash
pie install solarwinds/apm_ext
## verify installation
php --ri apm_ext
```

### 5. Export to a local SolarWinds OpenTelemtry Collector

Create a `config.yaml` file with the following content. Make sure to replace `<collector-name>` and `<solarwinds-otlp-endpoint>` with your actual values.
```yaml
receivers:
  otlp:
    protocols:
      grpc:
        endpoint: 0.0.0.0:4317
      http:
        endpoint: 0.0.0.0:4318

processors:
  batch:

extensions:
  solarwinds:
    collector_name: "<collector-name>" # Required parameter e.g. "my-collector"
    grpc: &grpc_settings
      endpoint: "<solarwinds-otlp-endpoint>" # Required parameter e.g. "otel.collector.na-01.cloud.solarwinds.com:443"
      tls:
        insecure: false
      headers:
        Authorization: "Bearer ${env:SOLARWINDS_API_TOKEN}"
        swi-reporter: "otel solarwinds-otel-collector"
exporters:
  otlp:
    <<: *grpc_settings

service:
  extensions: [solarwinds]
  pipelines:
    traces:
      receivers: [otlp]
      processors: [batch]
      exporters: [otlp]
    metrics:
      receivers: [otlp]
      processors: [batch]
      exporters: [otlp]
    logs:
      receivers: [otlp]
      processors: [batch]
      exporters: [otlp]
```
You can run the collector in a Docker container, make sure to replace `<solarwinds-api-token>` with your actual value:
```bash
docker run -e SOLARWINDS_API_TOKEN="<solarwinds-api-token>" -p 127.0.0.1:4317:4317 -p 127.0.0.1:4318:4318 -v ./config.yaml:/opt/default-config.yaml solarwinds/solarwinds-otel-collector:latest-verified
```

### 6. Run with tracing enabled and export to local collector
Restart the app with tracing using the following to send data to the local collector:
```bash
env OTEL_PHP_AUTOLOAD_ENABLED=true \
    OTEL_SERVICE_NAME=php-example \
    OTEL_TRACES_SAMPLER=solarwinds_http \
    OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions \
    OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS=xtrace,xtraceoptionsresponse \
    OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE=delta \
    SW_APM_SERVICE_KEY=<solarwinds-api-token>:php-example \
    OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION=base2_exponential_bucket_histogram \
    SW_APM_SERVICE_KEY=<token>:php-example \
    php -S localhost:8080
```

## Custom Transaction Name

Set a custom transaction name at the beginning of your application:
```php
use Solarwinds\ApmPhp\API\TransactionName;
TransactionName::set('custom-transaction-name');
```

## Upgrade from SolarWinds APM PHP library 8.x

If you are upgrading from version 8.x, fully uninstall the previous version before installing 9.x or later to avoid conflicts.
Instruction can be found in the [UPGRADE.md](./UPGRADE.md).


## Contributing

Contributions are welcome! See [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

Thanks to all contributors:
<a href="https://github.com/solarwinds/apm-php/graphs/contributors">
  <img src="https://contributors-img.web.app/image?repo=solarwinds/apm-php"/>
</a>

## Troubleshooting
For troubleshooting, refer to the [Troubleshooting Guide](./TROUBLESHOOTING.md).

## License

Apache-2.0. See [LICENSE](./LICENSE) for details.
