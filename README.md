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

Set the [collector endpoint](https://documentation.solarwinds.com/en/success_center/observability/content/system_requirements/endpoints.htm?#Find), default is `apm.collector.na-01.cloud.solarwinds.com`:
```bash
export SW_APM_COLLECTOR=<your-collector-url>
```

## Add-on solarwinds/apm_ext installation

Install [solarwinds/apm_ext](https://packagist.org/packages/solarwinds/apm_ext) for caching remote sampling settings to improve performance
```bash
pie install solarawinds/apm_ext
```

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

Add SolarWinds APM Library and required dependencies. More instrumentation libraries can be found [here](https://packagist.org/packages/open-telemetry/?query=open-telemetry%2Fopentelemetry-):
```bash
composer config allow-plugins.php-http/discovery false
composer require guzzlehttp/guzzle solarwinds/apm open-telemetry/opentelemetry-auto-slim open-telemetry/exporter-otlp
```

### 3. Run with tracing enabled

Get your `<token>` from [SolarWinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration).

Start the app with tracing:
```bash
env OTEL_PHP_AUTOLOAD_ENABLED=true \
    OTEL_SERVICE_NAME=php-example \
    OTEL_TRACES_SAMPLER=solarwinds_http \
    OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions \
    OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS=xtrace,xtraceoptionsresponse \
    OTEL_EXPORTER_OTLP_ENDPOINT=https://otel.collector.na-01.cloud.solarwinds.com:443 \
    OTEL_EXPORTER_OTLP_HEADERS="Authorization=Bearer <token>" \
    SW_APM_SERVICE_KEY=<token>:php-example \
    php -S localhost:8080
```

Reload [http://localhost:8080/rolldice](http://localhost:8080/rolldice) and view traces in the SolarWinds Observability platform.

<img width="616" alt="SWO" src="https://github.com/user-attachments/assets/ed312cc8-ebd7-4c4e-bce3-bac882843200" />

---

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
