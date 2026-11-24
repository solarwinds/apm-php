# Opentelemetry Helm Chart Support for apm-php

To install apm-php in a Kubernetes cluster using the [opentelemetry-helm-chart](https://github.com/open-telemetry/opentelemetry-helm-charts) , follow the instructions in the [opentelemetry-helm-chart operator documentation](https://github.com/open-telemetry/opentelemetry-helm-charts/blob/main/charts/opentelemetry-operator/README.md).

> [!TIP]
> PHP auto-instrumentation is disabled by default in opentelemetry-operator. Enable it by setting `autoInstrumentation.php.enabled=true` in the helm chart values.

Example
```
manager:
  autoInstrumentationImage:
    php:
      repository: "ghcr.io/solarwinds/autoinstrumentation-php"
      tag: "2.0.1"

  # The PHP instrumentation support in the operator is disabled by default.
  autoInstrumentation:
    php:
      enabled: true
```

Follow [opentelemetry-operator support for apm-php](./opentelemetry-operator-support.md) to setup `spec.php.env` and pod annotations for your PHP application pod.
