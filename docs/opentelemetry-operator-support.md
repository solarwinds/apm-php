# Opentelemetry Operator Support for apm-php

To install apm-php in a Kubernetes cluster using the [opentelemetry-operator](https://github.com/open-telemetry/opentelemetry-operator), follow the instructions in the [opentelemetry-operator Auto-instrumentation documentation](https://github.com/open-telemetry/opentelemetry-operator/blob/main/docs/auto-instrumentation/README.md).

> [!TIP]
> PHP auto-instrumentation is disabled by default in opentelemetry-operator. Start the operator with --enable-php-instrumentation=true (or set ENABLE_PHP_AUTO_INSTRUMENTATION=true) to enable it.

Solarwinds provided autoinstrumentation-php image is available at [ghcr.io/solarwinds/autoinstrumentation-php](https://ghcr.io/solarwinds/autoinstrumentation-php).

Example of Instrumentation resource for apm-php:
```
apiVersion: opentelemetry.io/v1alpha1
kind: Instrumentation
metadata:
  name: my-instrumentation
spec:
  php:
    image: ghcr.io/solarwinds/autoinstrumentation-php:2.0.1
    env:
      - name: OTEL_SERVICE_NAME
        value: "my-service"
      - name: OTEL_TRACES_SAMPLER
        value: "swo_http"
      - name: OTEL_PROPAGATORS
        value: "baggage,tracecontext,swo_tracestate,swo_xtraceoptions"
      - name: OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS
        value: "swo_xtrace,swo_xtraceoptionsresponse"
      - name: OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE
        value: "delta"
      - name: OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION
        value: "base2_exponential_bucket_histogram"
      - name: OTEL_EXPORTER_OTLP_ENDPOINT
        value: "<solarwinds-otlp-endpoint>"
      - name: OTEL_EXPORTER_OTLP_HEADERS
        value: "Authorization=Bearer <solarwinds-token>"
      - name: SW_APM_COLLECTOR
        value: "<solarwinds-apm-collector>"
      - name: SW_APM_SERVICE_KEY
        value: "<solarwinds-token>"
```

You also need to set up pod annotations to inject the PHP instrumentation into your PHP application pod. For example, if you have a deployment named `my-php-app`, you can add the following annotation to the pod template:

Assume you have a pod named `my-php-app` running PHP 8.5.x (nts) in Debian, you can add the following annotations to the pod spec:
```
apiVersion: v1
kind: Pod
metadata:
  name: my-php-app
  annotations:
    instrumentation.opentelemetry.io/inject-php: "true"
    instrumentation.opentelemetry.io/otel-php-api-version: "20250925"
```
