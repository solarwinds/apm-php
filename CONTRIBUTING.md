# Issues

## Security issues

Please report any security issues privately to the SolarWinds Product Security Incident Response Team (PSIRT) at [psirt@solarwinds.com](mailto:psirt@solarwinds.com).

## All other issues

For non-security issues, please submit your ideas, questions, or problems as [GitHub issues](https://github.com/solarwinds/apm-php/issues). Please add as much information as you can, such as: PHP version, platform, installed dependencies and their version numbers, hosting, code examples or gists, steps to reproduce, stack traces, and logs. SolarWinds project maintainers may ask for clarification or more context after submission.

# Contributing Guide

This project builds on the [OpenTelemetry PHP SDK](https://github.com/open-telemetry/opentelemetry-php) and follows its [contributing guide](https://github.com/open-telemetry/opentelemetry-php/blob/main/CONTRIBUTING.md). To contribute, ensure you have PHP 8.1+ and Docker installed. After cloning, copy `.env.dist` to `.env` and set a valid SolarWinds Observability API token and service name in the `SW_APM_SERVICE_KEY` variable (available from [SolarWinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration)).

To install dependencies, build, and run tests locally, use:

```bash
make install      # Install dependencies
make all          # Build and run all checks and tests
make test         # Run the test suite
```

For more details or advanced workflows, refer to the upstream OpenTelemetry PHP contributing guide. Open a pull request on GitHub when you are ready to propose changes.
