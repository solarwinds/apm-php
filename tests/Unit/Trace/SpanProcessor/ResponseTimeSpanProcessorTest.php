<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\SpanProcessor;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Metrics\Data\Histogram;
use OpenTelemetry\SDK\Metrics\MeterProviderBuilder;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use OpenTelemetry\SDK\Trace\RandomIdGenerator;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\StatusData;
use OpenTelemetry\SemConv\TraceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use Solarwinds\ApmPhp\Trace\SpanProcessor\ResponseTimeSpanProcessor;

#[CoversClass(ResponseTimeSpanProcessor::class)]
class ResponseTimeSpanProcessorTest extends MockeryTestCase
{
    private ResponseTimeSpanProcessor $responseTimeSpanProcessor;
    private InMemoryExporter $exporter;
    private MetricReaderInterface $reader;
    /** @var MockInterface&ReadableSpanInterface */
    private $readableSpan;
    /** @var MockInterface&ReadWriteSpanInterface */
    private $readWriteSpan;

    protected function setUp(): void
    {
        $this->readableSpan = Mockery::mock(ReadableSpanInterface::class);
        $this->readWriteSpan = Mockery::mock(ReadWriteSpanInterface::class);
        $this->exporter = new InMemoryExporter();
        $this->reader = new ExportingReader($this->exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($this->reader)->build();
        $this->responseTimeSpanProcessor = new ResponseTimeSpanProcessor($meterProvider);
    }
    public function test_span_ok(): void
    {
        $this->readableSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_OK, 'ok'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_SERVER);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturn('transaction');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->andReturn('GET');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->andReturn(200);
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->andReturnNull();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertFalse($attributes['sw.is_error'] ?? true);
                    $this->assertEquals('transaction', $attributes[ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE] ?? '');
                    $this->assertEquals('GET', $attributes[TraceAttributes::HTTP_REQUEST_METHOD] ?? '');
                    $this->assertEquals(200, $attributes[TraceAttributes::HTTP_RESPONSE_STATUS_CODE] ?? '');
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_STATUS_CODE, $attributes);
                }
            }
        }
    }
    public function test_span_ok_spankind(): void
    {
        $this->readableSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_OK, 'ok'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_CLIENT);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturn('transaction');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->never();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->never();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->never();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->never();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertFalse($attributes['sw.is_error'] ?? true);
                    $this->assertEquals('transaction', $attributes[ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE] ?? '');
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_REQUEST_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_STATUS_CODE, $attributes);
                }
            }
        }
    }
    public function test_span_ok_depreciated_fields(): void
    {
        $this->readableSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_OK, 'ok'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_SERVER);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturn('transaction');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->andReturn('GET');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->andReturn(200);
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->andReturnNull();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertFalse($attributes['sw.is_error'] ?? true);
                    $this->assertEquals('transaction', $attributes[ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE] ?? '');
                    $this->assertEquals('GET', $attributes[TraceAttributes::HTTP_METHOD] ?? '');
                    $this->assertEquals(200, $attributes[TraceAttributes::HTTP_STATUS_CODE] ?? '');
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_REQUEST_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $attributes);
                }
            }
        }
    }
    public function test_local_parent(): void
    {
        $generator = new RandomIdGenerator();
        $validLocalParentContext = SpanContext::create(
            $generator->generateTraceId(),
            $generator->generateSpanId(),
            TraceFlags::SAMPLED
        );
        $this->readableSpan->expects('getParentContext')->andReturn($validLocalParentContext);
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(0, $metric->data->dataPoints);
            }
        }
    }
    public function test_remote_parent_ok(): void
    {
        $generator = new RandomIdGenerator();
        $validRemoteParentContext = SpanContext::createFromRemoteParent(
            $generator->generateTraceId(),
            $generator->generateSpanId(),
            TraceFlags::SAMPLED
        );
        $this->readableSpan->expects('getParentContext')->andReturn($validRemoteParentContext);
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_OK, 'ok'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_SERVER);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturn('transaction');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->andReturn('GET');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->andReturn(200);
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->andReturnNull();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertFalse($attributes['sw.is_error'] ?? true);
                    $this->assertEquals('transaction', $attributes[ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE] ?? '');
                    $this->assertEquals('GET', $attributes[TraceAttributes::HTTP_REQUEST_METHOD] ?? '');
                    $this->assertEquals(200, $attributes[TraceAttributes::HTTP_RESPONSE_STATUS_CODE] ?? '');
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_STATUS_CODE, $attributes);
                }
            }
        }
    }
    public function test_span_error(): void
    {
        $this->readableSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_ERROR, 'error'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_SERVER);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturn('transaction');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->andReturn('POST');
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->andReturn(400);
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->andReturnNull();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertTrue($attributes['sw.is_error'] ?? false);
                    $this->assertEquals('transaction', $attributes[ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE] ?? '');
                    $this->assertEquals('POST', $attributes[TraceAttributes::HTTP_REQUEST_METHOD] ?? '');
                    $this->assertEquals(400, $attributes[TraceAttributes::HTTP_RESPONSE_STATUS_CODE] ?? '');
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_STATUS_CODE, $attributes);
                }
            }
        }
    }
    public function test_span_unset(): void
    {
        $this->readableSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readableSpan->expects('getDuration')->andReturn(123456789);
        $spanData = Mockery::mock(SpanDataInterface::class);
        $spanData->expects('getStatus')->andReturn(StatusData::create(StatusCode::STATUS_UNSET, 'unset'));
        $this->readableSpan->expects('toSpanData')->andReturn($spanData);
        $this->readableSpan->expects('getKind')->andReturn(SpanKind::KIND_SERVER);
        $this->readableSpan->expects('getAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_REQUEST_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_RESPONSE_STATUS_CODE)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_METHOD)->andReturnNull();
        $this->readableSpan->expects('getAttribute')->with(TraceAttributes::HTTP_STATUS_CODE)->andReturnNull();
        $this->responseTimeSpanProcessor->onEnd($this->readableSpan);
        $this->reader->collect();
        $metrics = $this->exporter->Collect();
        // Check histogram
        $this->assertCount(1, $metrics);
        foreach ($metrics as $metric) {
            $this->assertEquals('trace.service.response_time', $metric->name);
            $this->assertEquals('ms', $metric->unit);
            if ($metric->data instanceof Histogram) {
                $this->assertEquals('Delta', $metric->data->temporality);
                $this->assertCount(1, $metric->data->dataPoints);
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $this->assertEquals(123.456789, $dataPoint->sum);
                    $attributes = [];
                    foreach ($dataPoint->attributes as $key => $value) {
                        $attributes[$key] = $value;
                    }
                    $this->assertFalse($attributes['sw.is_error'] ?? true);
                    $this->assertArrayNotHasKey(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_REQUEST_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_METHOD, $attributes);
                    $this->assertArrayNotHasKey(TraceAttributes::HTTP_STATUS_CODE, $attributes);
                }
            }
        }
    }

    public function test_entry_http_route(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn('SiteController.actionIndex');
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn('ok.opentelemetry.io/foo');
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'SiteController.actionIndex');
        $this->responseTimeSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_entry_url_path(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn(null);
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn('ok.opentelemetry.io/foo');
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, '/ok.opentelemetry.io/foo');
        $this->responseTimeSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_entry_span_name(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn(null);
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn(null);
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'span');
        $this->responseTimeSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_local_parent_span_context(): void
    {
        $generator = new RandomIdGenerator();
        $validLocalParentContext = SpanContext::create(
            $generator->generateTraceId(),
            $generator->generateSpanId(),
            TraceFlags::SAMPLED
        );
        $this->readWriteSpan->expects('getParentContext')->andReturn($validLocalParentContext);
        $this->readWriteSpan->shouldNotReceive('setAttribute');
        $this->responseTimeSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_remote_parent_span_context(): void
    {
        $generator = new RandomIdGenerator();
        $validRemoteParentContext = SpanContext::createFromRemoteParent(
            $generator->generateTraceId(),
            $generator->generateSpanId(),
            TraceFlags::SAMPLED
        );
        $this->readWriteSpan->expects('getParentContext')->andReturn($validRemoteParentContext);
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn('SiteController.actionIndex');
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn('ok.opentelemetry.io/foo');
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'SiteController.actionIndex');
        $this->responseTimeSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_get_instance_returns_singleton(): void
    {
        $instance1 = ResponseTimeSpanProcessor::getInstance();
        $instance2 = ResponseTimeSpanProcessor::getInstance();
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(ResponseTimeSpanProcessor::class, $instance1);
    }

    public function test_transaction_name_truncation(): void
    {
        $processor = new ResponseTimeSpanProcessor();
        $pool = $processor->getTransactionNamePool();
        $longName = str_repeat('a', 300);
        $truncated = $pool->register($longName);
        $this->assertLessThanOrEqual(256, strlen($truncated));
    }

    public function test_transaction_name_default(): void
    {
        $processor = new ResponseTimeSpanProcessor();
        $pool = $processor->getTransactionNamePool();
        $default = $pool->register('');
        $this->assertEquals('', $default);
    }

    public function test_transaction_name_pool_limit(): void
    {
        $processor = new ResponseTimeSpanProcessor();
        $pool = $processor->getTransactionNamePool();
        // Fill the pool to its max size
        for ($i = 0; $i < 200; $i++) {
            $pool->register('name' . $i);
        }
        // Registering another name should return 'other'
        $result = $pool->register('overflow');
        $this->assertEquals('other', $result);
    }
}
