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

## PHP-Specific Considerations
- **open_basedir:** If using PHP's `open_basedir`, ensure it allows access to required OpenTelemetry files and directories.
- **Fibers:** If using `Fibers`, set `OTEL_PHP_FIBERS_ENABLED=true` and consider preloading bindings, especially for non-CLI SAPIs.
- **Stack Extension:** If you encounter issues with argument handling in pre-hooks, enable `opentelemetry.allow_stack_extension` in `php.ini`.

## SolarWinds Observability (SWO) Issues

### Verifying Telemetry Generation with OpenTelemetry Collector
- **Debug Exporter:** Change your exporter to `debug` to confirm traces, metrics, or logs are being generated and output to the console. This helps isolate issues between instrumentation and the collector/SWO.
  ```yaml
  receivers:
    otlp:
  exporters:
    debug:
  service:
    pipelines:
      traces:
        receivers: [otlp] # Or your chosen receiver
        exporters: [debug]
  ```
- **Detailed Logging:** Enable debug logs for more insight into instrumentation and errors:
  ```bash
  export OTEL_LOG_LEVEL=debug
  ```

### Troubleshooting SWO Export Issues
- **Collector Logs:** If data appears in the console but not in SWO, check OpenTelemetry Collector logs to verify data is received, processed, and exported correctly.
- **Configuration Verification:** Ensure your `SW_APM_SERVICE_KEY` is set correctly.

---

If you continue to experience issues, consult the [OpenTelemetry PHP documentation](https://opentelemetry.io/docs/instrumentation/php/) or the [SolarWinds Observability documentation](https://documentation.solarwinds.com/en/success_center/observability/default.htm) for further guidance.
