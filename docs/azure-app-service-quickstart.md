# Azure App Service Quickstart (PHP + SolarWinds APM)

This guide documents a working setup for running `solarwinds/apm` on Azure App Service for Linux with a SolarWinds OpenTelemetry Collector sidecar.

## Why this setup is different on Azure App Service

App Service containers can be replaced during platform operations. Any custom files stored outside `/home` are not guaranteed to persist.
Store extensions, `.ini` files, and collector config under `/home`.

## 1. Regular PHP App Service setup

### Prerequisites

- Azure App Service for PHP
- SSH access to the app container
- `solarwinds/apm` installed in your PHP app's `composer.json`
- `composer` and `pie` available in container shell (to install C extensions and manage dependencies)
- App settings access in Azure Portal

### Persist extensions under `/home` via ssh

Install apm_ext and opentelemetry extensions using `pie` command, then copy them to a persistent path. Uninstall the original extensions, and configure PHP to load them from `/home/site/ext`.:

```text
/home/site/ext/
  apm_ext.so
  opentelemetry.so
```

Example:

```bash
pie install open-telemetry/ext-opentelemetry
pie install solarwinds/apm_ext
mkdir -p /home/site/ext
# copy .so files into /home/site/ext
cp $(php -r "echo ini_get('extension_dir');")/opentelemetry.so /home/site/ext/
cp $(php -r "echo ini_get('extension_dir');")/apm_ext.so /home/site/ext/
pie uninstall solarwinds/apm_ext
pie uninstall open-telemetry/ext-opentelemetry
```

### Persist `.ini` files under `/home`

```text
/home/site/ini/apm_ext.ini
/home/site/ini/opentelemetry.ini
```

With absolute paths:

```ini
; /home/site/ini/apm_ext.ini
extension=/home/site/ext/apm_ext.so
```

```ini
; /home/site/ini/opentelemetry.ini
extension=/home/site/ext/opentelemetry.so
```

### Configure PHP to scan custom ini path

Set `PHP_INI_SCAN_DIR` in Azure App Service Settings to include `/home/site/ini` (along with the default scan dir).

```text
PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/home/site/ini
```

## 2. Run SolarWinds OTel Collector as sidecar

Run `swotel` collector as an App Service sidecar container.

### Persist collector config under `/home`

Store the following [configuration](https://github.com/solarwinds/solarwinds-otel-collector-releases/blob/main/examples/integrations/apm/config.yaml) in `/home/site/swotel/config.yaml`:

Fill in the `collector_name` and `endpoint` values:

Example:
```yaml
extensions:
  solarwinds:
    collector_name: <your-azure-app-azure-collector> # Required parameter
    grpc: &grpc_settings
      endpoint: https://otel.collector.na-01.cloud.solarwinds.com:443 # Required parameter
      tls:
        insecure: false
      headers: {"Authorization": "Bearer ${env:SOLARWINDS_TOKEN}", "swi-reporter": "otel solarwinds-otel-collector"}
```

### Required token for sidecar

Set `SOLARWINDS_TOKEN` in Azure App Service environment so sidecar config can reference it.

### Setup a sidecar container in Azure App Service

![Deployment Center](docs/images/deployment-center.png "Deployment Center")

![Edit container](docs/images/edit-container.png "Edit container")

Mount `/home/site/swotel/config.yaml` to `/opt/default-config.yaml` in the sidecar container, and set `SOLARWINDS_TOKEN` in the sidecar environment.

## 3. Typical app environment variables

Set the usual `apm-php` / OpenTelemetry variables in App Settings, such as:

- `SW_APM_SERVICE_KEY`
- `SW_APM_COLLECTOR`
- `OTEL_SERVICE_NAME`
- `OTEL_PHP_AUTOLOAD_ENABLED=true`
- `OTEL_TRACES_SAMPLER=solarwinds_http`
- `OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions`
- `OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS=xtrace,xtraceoptionsresponse`
- `OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE=delta`
- `OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION=base2_exponential_bucket_histogram`


## 4. Validation checklist

- `php --ri opentelemetry` shows extension loaded.
- `php --ri apm_ext` shows extension loaded.
- Swotel collector sidecar is healthy from collector logs.
- Azure App Service traces appear in SolarWinds Observability.

## 5. WordPress setup (Azure App Service image)

For `appsvc/wordpress-debian-php`, setup is mostly identical, with two differences:

1. **OpenTelemetry C extension is already included**
   No manual `opentelemetry.so` installation is needed.
2. **Instrumentation is injected without modifying WordPress app code**
   Create a separate Composer project (for example `/home/site/otel`) and preload its autoloader using `auto_prepend_file`.

### Example instrumentation project `composer.json`

```json
{
  "name": "azure-app-service/instrument-azure-wordpress",
  "type": "project",
  "require": {
    "solarwinds/apm": "^9.0@alpha",
    "open-telemetry/api": "^1.7",
    "open-telemetry/detector-azure": "^0.2",
    "open-telemetry/opentelemetry-auto-wordpress": "^0.2",
    "symfony/http-client": "^8.1"
  },
  "config": {
    "allow-plugins": {
      "php-http/discovery": true,
      "tbachert/spi": true
    }
  }
}
```

### WordPress prepend ini

```ini
; e.g. /home/site/ini/otel-autoload.ini
auto_prepend_file=/home/site/otel/vendor/autoload.php
```

Everything else stays the same: persist custom artifacts under `/home`, use `PHP_INI_SCAN_DIR`, run `swotel` sidecar.
