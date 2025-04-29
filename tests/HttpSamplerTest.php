<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Configuration;
use Solarwinds\ApmPhp\HttpSampler;

#[CoversClass(HttpSampler::class)]
class HttpSamplerTest extends TestCase
{
    public function test_valid_service_key_samples_created_spans(): void
    {
        $serviceKey = getenv('SW_APM_SERVICE_KEY');
        if ($serviceKey === false) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set.');
        }
        [$token, $service] = explode(':', $serviceKey);
        $spanExporter = new InMemoryExporter();
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://apm.collector.na-01.cloud.solarwinds.com', ['Authorization: Bearer ' . $token,], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        // $sampler->waitUntilReady(1000);
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertTrue($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertArrayHasKey('SampleRate', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('SampleSource', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('BucketCapacity', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('BucketRate', $spans[0]->getAttributes()->toArray());
    }

    public function test_invalid_service_key_does_not_sample_created_spans(): void
    {
        $spanExporter = new InMemoryExporter();
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'https://apm.collector.na-01.cloud.solarwinds.com', ['Authorization: Bearer oh no',], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        // $sampler->waitUntilReady(1000);
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_invalid_collector_does_not_sample_created_spans(): void
    {
        $serviceKey = getenv('SW_APM_SERVICE_KEY');
        if ($serviceKey === false) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set.');
        }
        [$token, $service] = explode(':', $serviceKey);
        $spanExporter = new InMemoryExporter();
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://collector.invalid', ['Authorization: Bearer ' . $token,], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        // $sampler->waitUntilReady(1000);
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }
}
