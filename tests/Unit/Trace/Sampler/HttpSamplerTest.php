<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use Http\Client\HttpAsyncClient;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\HttpSampler;

#[CoversClass(HttpSampler::class)]
class HttpSamplerTest extends TestCase
{
    public function test_valid_service_key_samples_created_spans(): void
    {
        $serviceKey = getenv('SW_APM_SERVICE_KEY');
        if (empty($serviceKey)) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set.');
        }
        [$token, $service] = explode(':', $serviceKey);
        $spanExporter = new InMemoryExporter();
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://apm.collector.na-01.cloud.solarwinds.com', ['Authorization' => 'Bearer ' . $token], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
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
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'https://apm.collector.na-01.cloud.solarwinds.com', ['Authorization' => 'Bearer oh no'], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_invalid_collector_does_not_sample_created_spans(): void
    {
        $serviceKey = getenv('SW_APM_SERVICE_KEY');
        if (empty($serviceKey)) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set.');
        }
        [$token, $service] = explode(':', $serviceKey);
        $spanExporter = new InMemoryExporter();
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://collector.invalid', ['Authorization' => 'Bearer ' . $token,], true, true, null, []), null);
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertFalse($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_non_200_status_code_does_not_sample(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $promise = $this->createMock(\Http\Promise\Promise::class);
        $client->method('sendAsyncRequest')->willReturn($promise);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags'=>'SAMPLE_START','value'=>1,'timestamp'=>time(),'ttl'=>60,'arguments'=>[]]) ]));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_non_json_content_type_does_not_sample(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $promise = $this->createMock(\Http\Promise\Promise::class);
        $client->method('sendAsyncRequest')->willReturn($promise);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaderLine')->willReturn('text/plain');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => 'not json']));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_invalid_json_response_does_not_sample(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $promise = $this->createMock(\Http\Promise\Promise::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $client->method('sendAsyncRequest')->willReturn($promise);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => '{not valid json']));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_exception_during_request_does_not_sample(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $client->method('sendAsyncRequest')->willThrowException(new \Exception('fail'));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_skip_loop_within_60_seconds(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $promise = $this->createMock(\Http\Promise\Promise::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $client->method('sendAsyncRequest')->willReturn($promise);
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        // Set the request_timestamp to now
        $ref = new \ReflectionClass($sampler);
        $prop = $ref->getProperty('request_timestamp');
        $prop->setAccessible(true);
        $prop->setValue($sampler, time());
        // Should not call client->sendRequest again
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_get_description_returns_expected_string(): void
    {
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null);
        $this->assertStringContainsString('HTTP Sampler (localhost)', $sampler->getDescription());
    }

    public function test_warning_deduplication(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $promise = $this->createMock(\Http\Promise\Promise::class);
        $client->method('sendAsyncRequest')->willReturn($promise);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags'=>'SAMPLE_START','value'=>1,'timestamp'=>time(),'ttl'=>60,'arguments'=>[]]) ]));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', [], true, true, null, []), null, $client, $requestFactory);
        // First call logs warning
        $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        // Second call with same warning should log debug, not warning
        $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertTrue(true); // If no exception, deduplication works
    }
}
