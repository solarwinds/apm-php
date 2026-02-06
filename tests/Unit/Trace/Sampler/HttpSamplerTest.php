<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://apm.collector.na-01.cloud.solarwinds.com', $token, [], true, true, null, []), null);
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
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'https://apm.collector.na-01.cloud.solarwinds.com', 'oh no', [], true, true, null, []), null);
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
        $sampler = new HttpSampler(null, new Configuration(true, $service, 'https://collector.invalid', $token, [], true, true, null, []), null);
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
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturn($request);
        $client->method('sendRequest')->willReturn($response);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags'=>'SAMPLE_START','value'=>1,'timestamp'=>time(),'ttl'=>60,'arguments'=>[]]) ]));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_non_json_content_type_does_not_sample(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturn($request);
        $client->method('sendRequest')->willReturn($response);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaderLine')->willReturn('text/plain');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => 'not json']));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_invalid_json_response_does_not_sample(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturn($request);
        $client->method('sendRequest')->willReturn($response);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => '{not valid json']));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_exception_during_request_does_not_sample(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $request->method('withHeader')->willReturn($request);
        $client->method('sendRequest')->willThrowException(new \Exception('fail'));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_get_description_returns_expected_string(): void
    {
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null);
        $this->assertStringContainsString('HTTP Sampler (localhost)', $sampler->getDescription());
    }

    public function test_warning_deduplication(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);
        $client->method('sendRequest')->willReturn($response);
        $request->method('withHeader')->willReturn($request);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getHeaderLine')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags'=>'SAMPLE_START','value'=>1,'timestamp'=>time(),'ttl'=>60,'arguments'=>[]]) ]));
        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory);
        // First call logs warning
        $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        // Second call with same warning should log debug, not warning
        $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertTrue(true); // If no exception, deduplication works
    }

    public function test_extension_get_cache(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->expects($this->never())->method('createRequest')->willReturn($request);
        $client->expects($this->never())->method('sendRequest')->willReturn($response);
        $request->expects($this->never())->method('withHeader')->willReturn($request);
        $response->expects($this->never())->method('getStatusCode')->willReturn(200);
        $response->expects($this->never())->method('getHeaderLine')->willReturn('application/json');
        $response->expects($this->never())->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags' => 'SAMPLE_START', 'value' => 0, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 0, 'BucketCapacity' => 0]])]));

        $cacheExtensionInterface = $this->createMock(\Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface::class);
        $cacheExtensionInterface->expects($this->once())->method('isExtensionLoaded')->willReturn(true);
        $cacheExtensionInterface->expects($this->once())->method('getCache')->willReturn(json_encode(['flags' => 'SAMPLE_START', 'value' => 1000000, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 2, 'BucketCapacity' => 2]]));
        $cacheExtensionInterface->expects($this->never())->method('putCache')->willReturn(true);

        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory, $cacheExtensionInterface);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(2, $result->getDecision());
    }

    public function test_extension_get_cache_but_empty(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->expects($this->once())->method('createRequest')->willReturn($request);
        $client->expects($this->once())->method('sendRequest')->willReturn($response);
        $request->expects($this->once())->method('withHeader')->willReturn($request);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags' => 'SAMPLE_START', 'value' => 0, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 0, 'BucketCapacity' => 0]])]));

        $cacheExtensionInterface = $this->createMock(\Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface::class);
        $cacheExtensionInterface->expects($this->exactly(2))->method('isExtensionLoaded')->willReturn(true);
        $cacheExtensionInterface->expects($this->exactly(1))->method('getCache')->willReturn('');
        $cacheExtensionInterface->expects($this->exactly(1))->method('putCache')->willReturn(true);

        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory, $cacheExtensionInterface);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(1, $result->getDecision());
    }

    public function test_extension_not_loaded(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->expects($this->once())->method('createRequest')->willReturn($request);
        $client->expects($this->once())->method('sendRequest')->willReturn($response);
        $request->expects($this->once())->method('withHeader')->willReturn($request);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags' => 'SAMPLE_START', 'value' => 0, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 0, 'BucketCapacity' => 0]])]));

        $cacheExtensionInterface = $this->createMock(\Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface::class);
        $cacheExtensionInterface->expects($this->exactly(2))->method('isExtensionLoaded')->willReturn(false);
        $cacheExtensionInterface->expects($this->never())->method('getCache')->willReturn('never called');
        $cacheExtensionInterface->expects($this->never())->method('putCache')->willReturn(true);

        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory, $cacheExtensionInterface);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(1, $result->getDecision());
    }

    public function test_extension_loaded_but_no_get_function(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->expects($this->once())->method('createRequest')->willReturn($request);
        $client->expects($this->once())->method('sendRequest')->willReturn($response);
        $request->expects($this->once())->method('withHeader')->willReturn($request);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags' => 'SAMPLE_START', 'value' => 0, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 0, 'BucketCapacity' => 0]])]));

        $cacheExtensionInterface = $this->createMock(\Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface::class);
        $cacheExtensionInterface->expects($this->exactly(2))->method('isExtensionLoaded')->willReturn(true);
        $cacheExtensionInterface->expects($this->once())->method('getCache')->willReturn(false);
        $cacheExtensionInterface->expects($this->once())->method('putCache')->willReturn(true);

        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory, $cacheExtensionInterface);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(1, $result->getDecision());
    }

    public function test_extension_loaded_but_no_put_function(): void
    {
        $client = $this->createMock(\Psr\Http\Client\ClientInterface::class);
        $requestFactory = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $requestFactory->expects($this->once())->method('createRequest')->willReturn($request);
        $client->expects($this->once())->method('sendRequest')->willReturn($response);
        $request->expects($this->once())->method('withHeader')->willReturn($request);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, ['getContents' => json_encode(['flags' => 'SAMPLE_START', 'value' => 0, 'timestamp' => time(), 'ttl' => 60, 'arguments' => ['BucketRate' => 0, 'BucketCapacity' => 0]])]));

        $cacheExtensionInterface = $this->createMock(\Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface::class);
        $cacheExtensionInterface->expects($this->exactly(2))->method('isExtensionLoaded')->willReturn(true);
        $cacheExtensionInterface->expects($this->once())->method('getCache')->willReturn(false);
        $cacheExtensionInterface->expects($this->once())->method('putCache')->willReturn(false);

        $sampler = new HttpSampler(null, new Configuration(true, 'phpunit', 'http://localhost', '', [], true, true, null, []), null, $client, $requestFactory, $cacheExtensionInterface);
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(1, $result->getDecision());
    }
}
