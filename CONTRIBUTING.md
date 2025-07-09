# Contributing Guide

## Introduction

Solarwinds APM PHP library is built on top of the OpenTelemetry PHP SDK and has almost the same [contributing guide](https://github.com/open-telemetry/opentelemetry-php/blob/main/CONTRIBUTING.md) as opentelemetry-php.

This document provides guidelines for contributing to the Solarwinds APM PHP library, including setting up your development environment, running tests.

## Pre-requisites

To contribute effectively, ensure you have the following tools installed:

* PHP 8.1 or higher (Check supported PHP versions)

We aim to support officially supported PHP versions, according to https://www.php.net/supported-versions.php. The
developer image `ghcr.io/open-telemetry/opentelemetry-php/opentelemetry-php-base` is tagged as `8.1`, `8.2` and `8.3`
respectively, with `8.1` being the default. You can execute the test suite against other PHP versions by running the
following command:

```bash
PHP_VERSION=8.1 make all
#or
PHP_VERSION=8.3 make all
```
For repeatability and consistency across different operating systems, we use the [3 Musketeers pattern](https://3musketeers.pages.dev/). If you're on Windows, it might be a good idea to use Git bash for following the steps below.

**Note: After cloning the repository, copy `.env.dist` to `.env`, and update a valid SWO API key (can get it from [Solarwinds SaaS Free Trial](https://www.solarwinds.com/solarwinds-observability/registration)) to `SW_APM_SERVICE_KEY`.**

Skipping the step above would result in a "`The "PHP_USER" variable is not set. Defaulting to a blank string`" warning

We use `docker` and `docker compose` to perform a lot of our static analysis and testing. If you're planning to develop for this library, it'll help to install
[docker engine](https://docs.docker.com/engine/install/) and the [compose plugin](https://docs.docker.com/compose/install/).

Development tasks are generally run through a `Makefile`. Running `make` or `make help` will list available targets.

## Workflow

### Pull Requests

To propose changes to the codebase, you need
to [open a pull request](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request)
to the apm-php project.

After you open the pull request, the CI will run all the
associated [github actions](https://github.com/solarwinds/apm-php/actions/workflows/php.yml).

To ensure your PR doesn't emit a failure with GitHub actions, it's recommended that you run the important CI tests locally with the following command:

```bash
make all # composer update, then run all checks
make all-lowest # composer update to lowest dependencies, then run all checks
```

This does the following things:

* Installs/updates all the required dependencies for the project
* Uses [Rector](https://github.com/rectorphp/rector) to refactor your code according to our standards.
* Uses [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) to style your code using our style preferences.
* Uses [Deptrac](https://github.com/qossmic/deptrac) to check for dependency violations inside our code base
* Makes sure the composer files for the different components are valid
* Runs all of our [phpunit](https://phpunit.de/) unit tests.
* Performs static analysis with [Phan](https://github.com/phan/phan), [Psalm](https://psalm.dev/)
  and [PHPStan](https://phpstan.org/user-guide/getting-started)

## Local Run/Build

To ensure you have all the correct packages installed locally in your dev environment, you can run

```bash
make install
```

This will install all the library dependencies to
the `/vendor` directory.

To update these dependencies, you can run

```bash
make update
```

To downgrade to the lowest dependencies, you can run

```shell
make update-lowest
```

To run all checks without doing a composer update:

```shell
make all-checks
```
## Testing

To make sure the tests in this repo work as you expect, you can use the included docker test wrapper.  
To run the test suite, execute

```bash
make test
```

This will output the test output as well as a test coverage analysis (text + html - see `tests/coverage/html`). Code
that doesn't pass our currently defined tests will emit a failure in CI
