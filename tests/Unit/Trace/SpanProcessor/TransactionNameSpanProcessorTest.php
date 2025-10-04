<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\SpanProcessor;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\RandomIdGenerator;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SemConv\TraceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNameSpanProcessor;

#[CoversClass(TransactionNameSpanProcessor::class)]
class TransactionNameSpanProcessorTest extends MockeryTestCase
{
    private TransactionNameSpanProcessor $transactionNameSpanProcessor;
    /** @var MockInterface&ReadWriteSpanInterface */
    private $readWriteSpan;

    protected function setUp(): void
    {
        $this->readWriteSpan = Mockery::mock(ReadWriteSpanInterface::class);
        $this->transactionNameSpanProcessor = new TransactionNameSpanProcessor();
    }
    public function test_entry_http_route(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn('SiteController.actionIndex');
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn('ok.opentelemetry.io/foo');
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'SiteController.actionIndex');
        $this->transactionNameSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_entry_url_path(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn(null);
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn('ok.opentelemetry.io/foo');
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, '/ok.opentelemetry.io/foo');
        $this->transactionNameSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_entry_span_name(): void
    {
        $this->readWriteSpan->expects('getParentContext')->andReturn(SpanContext::getInvalid());
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::HTTP_ROUTE)->andReturn(null);
        $this->readWriteSpan->expects('getAttribute')->with(TraceAttributes::URL_PATH)->andReturn(null);
        $this->readWriteSpan->expects('getName')->andReturn('span');
        $this->readWriteSpan->shouldReceive('setAttribute')->with(TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'span');
        $this->transactionNameSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
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
        $this->transactionNameSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
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
        $this->readWriteSpan->shouldReceive('setAttribute')->with(TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, 'SiteController.actionIndex');
        $this->transactionNameSpanProcessor->onStart($this->readWriteSpan, Context::getCurrent());
    }
    public function test_get_instance_returns_singleton(): void
    {
        $instance1 = TransactionNameSpanProcessor::getInstance();
        $instance2 = TransactionNameSpanProcessor::getInstance();
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(TransactionNameSpanProcessor::class, $instance1);
    }

    public function test_transaction_name_truncation(): void
    {
        $processor = new TransactionNameSpanProcessor();
        $pool = $processor->getTransactionNamePool();
        $longName = str_repeat('a', 300);
        $truncated = $pool->register($longName);
        $this->assertLessThanOrEqual(256, strlen($truncated));
    }

    public function test_transaction_name_default(): void
    {
        $processor = new TransactionNameSpanProcessor();
        $pool = $processor->getTransactionNamePool();
        $default = $pool->register('');
        $this->assertEquals('', $default);
    }

    public function test_transaction_name_pool_limit(): void
    {
        $processor = new TransactionNameSpanProcessor();
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
