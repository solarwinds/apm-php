# PHP solarwinds-apm (In development)

![Packagist Version](https://img.shields.io/packagist/v/solarwinds/apm)
![Packagist Downloads](https://img.shields.io/packagist/dm/solarwinds/apm)
[![PHP CI](https://github.com/solarwinds/apm-php/actions/workflows/php.yml/badge.svg)](https://github.com/solarwinds/apm-php/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/solarwinds/apm-php/graph/badge.svg?token=g4IzcxmTSG)](https://codecov.io/github/solarwinds/apm-php)
[![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/solarwinds/apm-php/badge)](https://scorecard.dev/viewer/?uri=github.com/solarwinds/apm-php)
![GitHub License](https://img.shields.io/github/license/solarwinds/apm-php)


This repo holds the source code for the OpenTelemetry-based SolarWinds APM PHP library and its dependencies.

## Prerequisites
Solarwinds APM PHP library is built on top of the OpenTelemetry PHP SDK and has the same [prerequisites](https://opentelemetry.io/docs/languages/php/getting-started/#prerequisites) as opentelemetry-php.

Ensure that you have the following installed:
- [PHP 8.0+](https://www.php.net/)
- [PECL](https://pecl.php.net/)
- [composer](https://getcomposer.org/)

Before you get started make sure that you have both available in your shell:
```bash
php -v
composer -v
```

## Installation
Install the Solarwinds APM library using composer:
```bash
composer require solarwinds/apm
```

Same as OpenTelemetry SDK for PHP, in order to use `solarwinds/apm` and otlp exporter, you need packages that satisfy the dependencies for `psr/http-client-implementation` and `psr/http-factory-implementation`. An example is:
```bash
composer require guzzlehttp/guzzle
```

## Example application
Solarwinds APM PHP library is built on top of the OpenTelemetry PHP SDK and this section provides a simple example application that demonstrates how to use the library for automatic instrumentation.
It is inspired by the [OpenTelemetry PHP example application](https://opentelemetry.io/docs/languages/php/getting-started/#example-application).

### Dependencies
In an empty directory initialize a minimal `composer.json` file:
```bash
composer init \
  --no-interaction \
  --require slim/slim:"^4" \
  --require slim/psr7:"^1"
composer update
```
### Create and launch an HTTP Server
In that same directory, create a file called `index.php` with the following content:
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
Run the application using the PHP built-in web server:
```bash
php -S localhost:8080
```
Open http://localhost:8080/rolldice in your web browser to ensure it is working.

### Add zero-code instrumentation
Next, you’ll use the OpenTelemetry PHP extension to automatically instrument the application.

Follow [Install the Opentelemetry extension](https://opentelemetry.io/docs/zero-code/php/#install-the-opentelemetry-extension) to set up extension.

Verify that the extension is installed and enabled:
```bash
php --ri opentelemetry
```

Add additional dependencies to your application, which are required for the automatic instrumentation of your code:
```bash
composer config allow-plugins.php-http/discovery false
composer require \
  solarwinds/apm \
  open-telemetry/opentelemetry-auto-slim \
  open-telemetry/exporter-otlp
```
With the OpenTelemetry PHP extension set up and an instrumentation library installed, you can now run your application and generate some traces:


You can get the `<token>` from [Solarwinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration)
```bash
env OTEL_PHP_AUTOLOAD_ENABLED=true \
    OTEL_TRACES_EXPORTER=otlp \
    OTEL_METRICS_EXPORTER=otlp \
    OTEL_LOGS_EXPORTER=otlp \
    OTEL_SERVICE_NAME=php-example \
    OTEL_TRACES_SAMPLER=solarwinds_http \
    OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions \
    OTEL_EXPORTER_OTLP_ENDPOINT=https://otel.collector.na-01.cloud.solarwinds.com:443 \
    OTEL_EXPORTER_OTLP_HEADERS="Authorization=Bearer <token>" \
    SW_APM_SERVICE_KEY=<token>:php-example \
    php -S localhost:8080
```
Open http://localhost:8080/rolldice in your web browser and reload the page a few times. After a while you should see the trace in SWO platform:

![trace.png](./../png/trace.png)

## Contributing
Contributions are welcome!

For more information about contributing, see [CONTRIBUTING README](./CONTRIBUTING.md). Thank you to everyone who has contributed:

<a href="https://github.com/solarwinds/apm-php/graphs/contributors">
  <img src="https://contributors-img.web.app/image?repo=solarwinds/apm-php"/>
</a>

## License
Apache-2.0. See [LICENSE](./LICENSE) for details.
