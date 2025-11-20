# Contributing to SolarWinds APM PHP

Thank you for your interest in contributing! This project builds on the [OpenTelemetry PHP SDK](https://github.com/open-telemetry/opentelemetry-php) and welcomes community involvement.

## Reporting Issues

### Security Issues
If you discover a security vulnerability, please report it privately to the SolarWinds Product Security Incident Response Team (PSIRT) at [psirt@solarwinds.com](mailto:psirt@solarwinds.com).

### Other Issues
For bugs, feature requests, or questions, open a [GitHub issue](https://github.com/solarwinds/apm-php/issues). Please include:
- PHP version
- Platform and hosting details
- Installed dependencies and their versions
- Code examples or gists
- Steps to reproduce
- Stack traces and logs

Project maintainers may request additional information to help resolve your issue.

## Getting Started with Development

1. Ensure you have PHP 8.1+ and Docker installed.
2. Clone the repository.
3. Copy `.env.dist` to `.env` and set a valid SolarWinds Observability API token and service name in `SW_APM_SERVICE_KEY` (get a token from the [SolarWinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration)).

## Local Development Workflow

Use the following commands to set up and test your environment:
```bash
make install      # Install dependencies
make all          # Build and run all checks and tests
make test         # Run the test suite
```

For advanced workflows or more details, refer to the [OpenTelemetry PHP contributing guide](https://github.com/open-telemetry/opentelemetry-php/blob/main/CONTRIBUTING.md).

## Submitting Changes

- Fork the repository and create your feature branch.
- Make your changes and add tests as needed.
- Ensure all checks pass locally.
- Open a pull request on GitHub with a clear description of your changes.

---

Thank you for helping improve SolarWinds APM PHP!
