<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;

#[CoversClass(JsonSampler::class)]
class JsonSamplerTest extends TestCase
{
    private string $path;

    public function test_valid_file_samples_created_spans(): void
    {
        file_put_contents($this->path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time(),
                'ttl' => 60,
            ],
        ]));
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertTrue($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['BucketCapacity' => 100, 'BucketRate' => 10, 'SampleRate' => 1000000, 'SampleSource' => 6], $spans[0]->getAttributes()->toArray());
    }

    public function test_invalid_file_does_not_sample_created_spans(): void
    {
        file_put_contents($this->path, json_encode(['hello' => 'world']));
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_missing_file_does_not_sample_created_spans(): void
    {
        @unlink($this->path);
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_expired_file_does_not_sample_created_spans(): void
    {
        file_put_contents($this->path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time() - 120,
                'ttl' => 60,
            ],
        ]));
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_expired_file_samples_created_span_after_reading_new_settings(): void
    {
        file_put_contents($this->path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time() - 120,
                'ttl' => 60,
            ],
        ]));
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
        // Update the settings file to a valid state
        file_put_contents($this->path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time(),
                'ttl' => 60,
            ],
        ]));
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertTrue($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['BucketCapacity' => 100, 'BucketRate' => 10, 'SampleRate' => 1000000, 'SampleSource' => 6], $spans[0]->getAttributes()->toArray());
    }

    public function test_corrupt_json_file_does_not_sample(): void
    {
        file_put_contents($this->path, '{not valid json');
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $this->assertCount(0, $spanExporter->getSpans());
    }

    public function test_file_with_missing_fields_does_not_sample(): void
    {
        file_put_contents($this->path, json_encode([['value' => 1000000]])); // missing flags, timestamp, ttl
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $this->assertCount(0, $spanExporter->getSpans());
    }

    public function test_file_with_extra_fields_samples(): void
    {
        file_put_contents($this->path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time(),
                'ttl' => 60,
                'extra' => 'should be ignored',
            ],
        ]));
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), $this->path);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertTrue($span->isRecording());
        $span->end();
        $this->assertCount(1, $spanExporter->getSpans());
    }

    public function test_sampler_with_invalid_path_does_not_sample(): void
    {
        $spanExporter = new InMemoryExporter();
        $sampler = new JsonSampler(null, new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: []
        ), 'invalid');
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $this->assertCount(0, $spanExporter->getSpans());
    }

    public function test_description(): void
    {
        $sampler = new JsonSampler(null, new Configuration(
          service: 'test',
          collector: '',
          token: '',
          tracingMode: true,
          triggerTraceEnabled: true,
          transactionSettings: []
        ), $this->path);
        $this->assertStringContainsString('JSON Sampler (' . sys_get_temp_dir() . '/solarwinds-apm-settings.json', $sampler->getDescription());
    }

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/solarwinds-apm-settings.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }
}
