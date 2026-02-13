# Troubleshooting Guide

## General Troubleshooting Steps
- **Error Logs:** Check PHP's `error_log` for errors or warnings. Control the log destination with `OTEL_PHP_LOG_DESTINATION`.
- **OpenTelemetry C Extension:** Verify the `opentelemetry` C extension is loaded and active:
  ```bash
  php --ri opentelemetry
  ```
- **Minimal Reproducible Example:** Create a minimal PHP script to isolate the problem by sending a simple trace or metric.
- **Support:** If issues persist, reach out to SolarWinds support.

## Dependency Management
- **Composer:** Ensure all required OpenTelemetry PHP dependencies are listed in your `composer.json`. Check installed packages:
  ```bash
  composer show --installed
  ```
- **Version Compatibility:** Confirm package versions are compatible with your PHP version and each other.

## Environment Variable Configuration
- **Timing:** Set OpenTelemetry environment variables before including the Composer autoloader (e.g., before `vendor/autoload.php`). Setting them too late can prevent proper initialization.
- **Verification:** Use `printenv` to confirm all OpenTelemetry-related environment variables are set and accessible to your PHP application.
- **Common Environment Variables:**
  - `OTEL_PHP_AUTOLOAD_ENABLED`
  - `OTEL_SERVICE_NAME`
  - `OTEL_TRACES_SAMPLER`
  - `OTEL_PROPAGATORS`
  - `OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS`
  - `OTEL_EXPORTER_OTLP_ENDPOINT`
  - `OTEL_EXPORTER_OTLP_HEADERS`
  - `SW_APM_SERVICE_KEY`

## PHP-Specific Considerations
- **open_basedir:** If using PHP's `open_basedir`, ensure it allows access to required OpenTelemetry files and directories.
- **Fibers:** If using `Fibers`, set `OTEL_PHP_FIBERS_ENABLED=true` and consider preloading bindings, especially for non-CLI SAPIs.
- **Stack Extension:** If you encounter issues with argument handling in pre-hooks, enable `opentelemetry.allow_stack_extension` in `php.ini`.

## Sampling Considerations
- **solarwinds/apm_ext:** To troubleshoot sampling issues, it is often easier to temporarily disable the `solarwinds/apm_ext` C extension. You can do this by commenting out the extension in your `php.ini` file:
  ```ini
  ;extension=apm_ext
  ```

## SolarWinds Observability (SWO) Issues

### Verifying Telemetry Generation
- **Console Exporter:** Change your exporter to `console` to confirm traces, metrics, or logs are being generated and output to the console. This helps isolate issues between instrumentation and SWO.
  ```bash
  export OTEL_TRACES_EXPORTER=console
  export OTEL_METRICS_EXPORTER=console
  export OTEL_LOGS_EXPORTER=console
  ```
- **Detailed Logging:** Enable debug logs for more insight into instrumentation and errors:
  ```bash
  export OTEL_LOG_LEVEL=debug
  ```

### Troubleshooting SWO Export Issues
- **Error Logs:** If data appears in the console but not in SWO, check error logs to verify data is exported correctly.
- **Configuration Verification:** Ensure your `OTEL_EXPORTER_OTLP_HEADERS` and `OTEL_EXPORTER_OTLP_ENDPOINT` are set correctly.

---

If you continue to experience issues, consult the [OpenTelemetry PHP documentation](https://opentelemetry.io/docs/instrumentation/php/) for further guidance.
