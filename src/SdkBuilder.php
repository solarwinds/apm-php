<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use Composer\InstalledVersions;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Contrib\Otlp\StdoutLogsExporterFactory;
use OpenTelemetry\Contrib\Otlp\StdoutMetricExporterFactory;
use OpenTelemetry\Contrib\Otlp\StdoutSpanExporterFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Propagation\PropagatorFactory;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use RuntimeException;

class SdkBuilder
{
    public function buildAndRegisterGlobal() : ScopeInterface
    {
        if (InstalledVersions::isInstalled('solarwinds/apm-php')) {
            $version = InstalledVersions::getVersion('solarwinds/apm-php');
            if ($version === null) {
                throw new RuntimeException('Version not found');
            }
        }

        $attributes = [
            'sw.data.module' => 'apm',
            'sw.apm.version' => '0.0.0',
            ResourceAttributes::SERVICE_NAME => 'apm-php',
        ];
        $resource = ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create($attributes)));
        //        $reader = new ExportingReader(new MetricExporter(
        //            (new PsrTransportFactory())->create('http://localhost:4318/v1/metrics', 'application/x-protobuf')));
        $reader = new ExportingReader((new StdoutMetricExporterFactory())->create());
        $meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();
        //        $loggerProvider = LoggerProvider::builder()
        //            ->setResource($resource)
        //            ->addLogRecordProcessor(new SimpleLogRecordProcessor(new LogsExporter(
        //                (new PsrTransportFactory())->create('http://localhost:4318/v1/logs', 'application/x-protobuf'))))
        //            ->build();
        $loggerProvider = LoggerProvider::builder()
            ->setResource($resource)
            ->addLogRecordProcessor(new SimpleLogRecordProcessor((new StdoutLogsExporterFactory())->create()))
            ->build();
        //        $spanExporter = new SpanExporter(
        //            (new PsrTransportFactory())->create('http://localhost:4318/v1/traces', 'application/x-protobuf'));
        $spanExporter = (new StdoutSpanExporterFactory())->create();
        $token = '';
        $http_sampler = new HttpSampler($meterProvider, new Configuration(true, 'service', 'https://apm.collector.na-01.cloud.solarwinds.com', ['Authorization: Bearer ' . $token,], true, true, null, []));
        $tracerProvider = TracerProvider::builder()
            ->setResource($resource)
            ->addSpanProcessor(BatchSpanProcessor::builder($spanExporter)->setMeterProvider($meterProvider)->build())
            ->setSampler(new ParentBased($http_sampler, $http_sampler, $http_sampler))
            ->build();

        Registry::registerTextMapPropagator('swotracestate', new SwoTraceStatePropagator());
        Registry::registerTextMapPropagator('xtraceoptions', new XTraceOptionsPropagator());
        putenv('OTEL_PROPAGATORS=baggage,tracecontext,swotracestate,xtraceoptions');
        $propagator = (new PropagatorFactory())->create();
        return Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setMeterProvider($meterProvider)
            ->setLoggerProvider($loggerProvider)
            ->setPropagator($propagator)
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
    }
}
