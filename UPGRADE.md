# Upgrade Guide for SolarWinds APM PHP Library 9.x
This document outlines the steps and considerations for upgrading from SolarWinds APM PHP Library version 8.x to 9.x.

## Prerequisites
- Ensure you have a backup of your current application and its dependencies.
- Review the [releases](https://github.com/solarwinds/apm-php/releases)
- Familiarize yourself with the [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) for potential issues during the upgrade process.
- Check compatibility of your PHP version with the new library version.
- Review any custom instrumentation or integrations you have implemented.

## API changes from 8.x to 9.x
Starting with version 9, proprietary `solarwinds_apm_*` tracing API is no longer supported, standard PHP OTel API should be used instead. The table below lists the recommended API when upgrading to version 9.

| API                                 | 8\.x | 9\.x | Recommendation                                                                                                                               |
|-------------------------------------|------|------|----------------------------------------------------------------------------------------------------------------------------------------------|
| solarwinds_apm_get_context          | ✅    | 🚫   | Use [Context::getCurrent()](https://open-telemetry.github.io/opentelemetry-php/classes/OpenTelemetry-Context-Context.html#method_getCurrent) |
| solarwinds_apm_set_context          | ✅    | 🚫   | Not supported                                                                                                                                |
| solarwinds_apm_is_ready             | ✅    | 🚫   | 9\.x will be always ready before instrumentation                                                                                             |
| solarwinds_apm_start_trace          | ✅    | 🚫   | Use [Opentelemetry Trace API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-trace.html)                    |
| solarwinds_apm_end_trace            | ✅    | 🚫   | Use [Opentelemetry Trace API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-trace.html)                    |
| solarwinds_apm_set_transaction_name | ✅    | 🚫   | Use `TransactionName::set` API from `Solarwinds\ApmPhp\API`                                                                                  |
| solarwinds_apm_is_tracing           | ✅    | 🚫   | Not supported                                                                                                                                |
| solarwinds_apm_trace_started        | ✅    | 🚫   | Not supported                                                                                                                                |
| solarwinds_apm_log                  | ✅    | 🚫   | Use [Opentelemetry Log API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-logs.html)                       |
| solarwinds_apm_log_entry            | ✅    | 🚫   | Use [Opentelemetry Log API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-logs.html)                       |
| solarwinds_apm_log_exit             | ✅    | 🚫   | Use [Opentelemetry Log API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-logs.html)                       |
| solarwinds_apm_log_error            | ✅    | 🚫   | Use [Opentelemetry Log API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-logs.html)                       |
| solarwinds_apm_log_exception        | ✅    | 🚫   | Use [Opentelemetry Log API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-logs.html)                       |
| solarwinds_apm_metric_summary       | ✅    | 🚫   | Use [Opentelemetry Metrics API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-metrics.html)                |
| solarwinds_apm_metric_increment     | ✅    | 🚫   | Use [Opentelemetry Metrics API](https://open-telemetry.github.io/opentelemetry-php/namespaces/opentelemetry-api-metrics.html)                |
| solarwinds_apm_get_log_trace_id     | ✅    | 🚫   | Use [Context::getCurrent()](https://open-telemetry.github.io/opentelemetry-php/classes/OpenTelemetry-Context-Context.html#method_getCurrent) |
| TransactionName::set                | 🚫   | ✅    | 🚫                                                                                                                                           |

## Upgrade Steps
1. **Uninstall 8.x Version:**
   Before installing version 9.x, fully [uninstall](https://documentation.solarwinds.com/en/success_center/observability/content/configure/services/php/install.htm#link12) version 8.x to avoid conflicts.

2. **Install 9.x+ Version:**
   Follow the installation and Add zero-code instrumentation instructions in the [README.md](./README.md) to install version 9.x or later.

3. **Verify 9.x+ Installation:**
   Ensure the 9.x+ version is correctly installed and the OpenTelemetry extension is active:
   ```bash
   php --ri opentelemetry
   ```
   Verify the installation by following the Example Application in the [README.md](./README.md) to verify traces are being generated and sent to SolarWinds Observability.
