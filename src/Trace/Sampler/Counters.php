<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;

class Counters
{
    private readonly MeterInterface $meter;
    private readonly CounterInterface $request_count;
    private readonly CounterInterface $sample_count;
    private readonly CounterInterface $trace_count;
    private readonly CounterInterface $through_trace_count;
    private readonly CounterInterface $triggered_trace_count;
    private readonly CounterInterface $token_bucket_exhaustion_count;

    public function __construct(
        ?MeterProviderInterface $meterProvider = null,
    ) {
        $provider = $meterProvider ?? Globals::meterProvider();
        $this->meter = $provider->getMeter('sw.apm.sampling.metrics');
        $this->request_count = $this->meter->createCounter('trace.service.request_count');
        $this->sample_count = $this->meter->createCounter('trace.service.samplecount');
        $this->trace_count = $this->meter->createCounter('trace.service.tracecount');
        $this->through_trace_count = $this->meter->createCounter('trace.service.through_trace_count');
        $this->triggered_trace_count = $this->meter->createCounter('trace.service.triggered_trace_count');
        $this->token_bucket_exhaustion_count = $this->meter->createCounter('trace.service.tokenbucket_exhaustion_count');
    }

    public function getRequestCount(): CounterInterface
    {
        return $this->request_count;
    }

    public function getSampleCount(): CounterInterface
    {
        return $this->sample_count;
    }

    public function getTraceCount(): CounterInterface
    {
        return $this->trace_count;
    }

    public function getThroughTraceCount(): CounterInterface
    {
        return $this->through_trace_count;
    }

    public function getTriggeredTraceCount(): CounterInterface
    {
        return $this->triggered_trace_count;
    }

    public function getTokenBucketExhaustionCount(): CounterInterface
    {
        return $this->token_bucket_exhaustion_count;
    }
}
