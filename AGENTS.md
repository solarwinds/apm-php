# AGENTS.md

This document is a developer onboarding guide for telemetry agents/components in this repository, with direct links to setup, configuration, and operations docs.

## Scope

- This file is the **first-stop guide for new contributors** working on agent-related code.
- Detailed setup and configuration examples live in [README.md](./README.md) and [CONFIGURATION.md](./CONFIGURATION.md).
- Runtime issue diagnosis lives in [TROUBLESHOOTING.md](./TROUBLESHOOTING.md).

## Developer Onboarding Quick Start

Use this flow to get productive quickly in a local development environment.

1. Confirm prerequisites: PHP 8.1+, Docker, Composer, PECL, and `pie`.
2. Create local environment file and set service key:
   - copy `.env.dist` to `.env`
   - set `SW_APM_SERVICE_KEY=<token>:<service-name>`
3. Install dependencies and run unit tests.
4. Run all quality checks before opening a PR.

Common local workflow commands:

```bash
make install
make test
make all-checks
```

References: [CONTRIBUTING.md](./CONTRIBUTING.md), [README.md](./README.md)

## Supported Agents and Components

| Name                                            | Type                 | Language | Role                                                                                   | Integration Guide                                                        |
|-------------------------------------------------|----------------------|----------|----------------------------------------------------------------------------------------|--------------------------------------------------------------------------|
| SolarWinds APM PHP (`solarwinds/apm`)           | Library/Agent        | PHP      | OpenTelemetry-based instrumentation and SolarWinds integration for traces/metrics/logs | [README.md](./README.md)                                                 |
| SolarWinds APM Extension (`solarwinds/apm_ext`) | PHP C extension      | C/PHP    | Caches sampling settings to reduce request-path overhead                               | [README.md](./README.md), [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)     |
| OpenTelemetry PHP                               | Ecosystem dependency | PHP      | SDK/API, autoloading, propagators, exporter integration model                          | [OpenTelemetry PHP](https://github.com/open-telemetry/opentelemetry-php) |

## Compatibility Snapshot

Based on project docs, prepare the following before integration:

- PHP 8.1+
- Composer
- PECL
- `pie` (for installing optional PHP extensions)

Reference: [README.md](./README.md)

## Local Validation Commands

These targets are defined in [Makefile](./Makefile) and are the primary contributor workflow.

| Command              | Purpose                                                                         |
|----------------------|---------------------------------------------------------------------------------|
| `make test`          | Run unit tests                                                                  |
| `make test-coverage` | Run tests and generate coverage report                                          |
| `make style`         | Run PHP-CS-Fixer style checks/fixes                                             |
| `make phpstan`       | Run PHPStan static analysis                                                     |
| `make psalm`         | Run Psalm static analysis                                                       |
| `make phan`          | Run Phan static analysis                                                        |
| `make deptrac`       | Run architectural dependency checks                                             |
| `make rector`        | Run Rector in dry-run mode                                                      |
| `make all-checks`    | Run full quality gate (`rector`, `style`, `deptrac`, `phan`, `phpstan`, `test`) |

## Integration Paths

Two common deployment paths are documented:

1. **Direct OTLP export to SolarWinds Observability**
   - Configure OTLP endpoint/headers and service key.
   - Use SolarWinds sampler and propagators.
2. **Export via local OpenTelemetry Collector (recommended for minimizing export delays)**
   - App sends telemetry to local collector.
   - Collector forwards to SolarWinds Observability.

Reference setup and commands: [README.md](./README.md)

## Configuration Quick Reference

SolarWinds-specific settings are documented in [CONFIGURATION.md](./CONFIGURATION.md). Frequently used keys:

| Variable                           | Purpose                                              |
|------------------------------------|------------------------------------------------------|
| `SW_APM_SERVICE_KEY`               | Service identity/auth key (`<token>:<service-name>`) |
| `SW_APM_COLLECTOR`                 | SolarWinds APM collector endpoint                    |
| `SW_APM_TRACING_MODE`              | Enable/disable tracing behavior                      |
| `SW_APM_TRIGGER_TRACE`             | Enable/disable trigger trace behavior                |
| `SW_APM_TRANSACTION_NAME`          | Static custom transaction name                       |
| `SW_APM_TRANSACTION_SETTINGS`      | Inline JSON transaction settings                     |
| `SW_APM_TRANSACTION_SETTINGS_FILE` | File path for transaction settings JSON              |
| `SW_K8S_POD_NAMESPACE`             | Optional Kubernetes namespace override               |
| `SW_K8S_POD_UID`                   | Optional Kubernetes pod UID override                 |
| `SW_K8S_POD_NAME`                  | Optional Kubernetes pod name override                |

## Sampling Notes

- For lower sampling decision latency, the optional `solarwinds/apm_ext` extension caches sampling settings.
- If diagnosing sampling behavior, temporarily disabling `apm_ext` can simplify troubleshooting.

References: [README.md](./README.md), [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

## Troubleshooting Workflow

1. Verify extension/runtime state (for example, `opentelemetry` extension is loaded).
2. Confirm required environment variables are set before Composer autoload initialization.
3. Switch exporters to `console` to validate telemetry generation independently of backend ingestion.
4. Re-check OTLP endpoint and authentication headers if export succeeds locally but not in SWO.

Detailed steps and commands: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

## Common Pitfalls for New Contributors

- Set OpenTelemetry/SolarWinds environment variables **before** Composer autoload initialization.
- Verify required extensions first (`opentelemetry`; optionally `apm_ext`) before debugging SDK code.
- If telemetry appears locally but not in SWO, check OTLP endpoint and auth headers first.
- For sampling investigations, temporarily disable `apm_ext` to simplify behavior analysis.

Reference: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

## First Contribution Path

If you are new to the repository, use this low-risk path:

1. Start with a small docs or unit-test change.
2. Run `make test` for quick feedback.
3. Run `make all-checks` before opening a PR.
4. Open a PR with clear scope, reproduction context, and rationale.

Reference: [CONTRIBUTING.md](./CONTRIBUTING.md)

## Contributing, Security, and Upgrade Guides

- Contribution process: [CONTRIBUTING.md](./CONTRIBUTING.md)
- Security policy and reporting: [SECURITY.md](./SECURITY.md)
- Upgrade notes (including 8.x to 9.x guidance): [UPGRADE.md](./UPGRADE.md)

---

If you are unsure where to start, begin with [README.md](./README.md), then apply project-specific settings from [CONFIGURATION.md](./CONFIGURATION.md).

