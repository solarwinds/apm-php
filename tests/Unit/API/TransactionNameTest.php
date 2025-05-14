<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\API;

use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\API\TransactionName;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNameSpanProcessor;

#[CoversClass(TransactionName::class)]
class TransactionNameTest extends TestCase
{
    public function test_no_api_call(): void
    {
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new TransactionNameSpanProcessor())
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('testSpan')->startSpan();
        $scope = $span->activate();
        $scope->detach();
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals([TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE => 'testSpan'], $spans[0]->getAttributes()->toArray());
    }

    public function test_api_call_with_valid_local_root_span(): void
    {
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new TransactionNameSpanProcessor())
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('testSpan')->startSpan();
        $scope = $span->activate();
        $this->assertTrue(TransactionName::set('custom-name'));
        $scope->detach();
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals([TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE => 'custom-name'], $spans[0]->getAttributes()->toArray());
    }

    public function test_api_call_with_valid_local_root_span_empty(): void
    {
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new TransactionNameSpanProcessor())
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('testSpan')->startSpan();
        $scope = $span->activate();
        $this->assertFalse(TransactionName::set(''));
        $scope->detach();
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals([TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE => 'testSpan'], $spans[0]->getAttributes()->toArray());
    }

    public function test_api_call_out_of_activated_span(): void
    {
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new TransactionNameSpanProcessor())
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('testSpan')->startSpan();
        // Unable to set transaction name before span is activated
        $this->assertFalse(TransactionName::set('custom-name'));
        $scope = $span->activate();
        $scope->detach();
        // Unable to set transaction name after the span is deactivated
        $this->assertFalse(TransactionName::set('custom-name'));
        $span->end();
        // Of course unable to set transaction name after the span is ended
        $this->assertFalse(TransactionName::set('custom-name'));
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals([TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE => 'testSpan'], $spans[0]->getAttributes()->toArray());
    }

    public function test_set_transaction_name_to_local_root_span(): void
    {
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new TransactionNameSpanProcessor())
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler(new AlwaysOnSampler())
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('testSpan')->startSpan();
        $scope = $span->activate();
        $childSpan = $tracerProvider->getTracer('test')->spanBuilder('testSpanChild')->startSpan();
        $childSpan->setAttribute('child', 'child');
        // Set transaction name to the local root span
        $this->assertTrue(TransactionName::set('custom-name'));
        $childSpan->end();
        $scope->detach();
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(2, $spans);
        $this->assertEquals(['child' => 'child'], $spans[0]->getAttributes()->toArray());
        $this->assertEquals([TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE => 'custom-name'], $spans[1]->getAttributes()->toArray());
    }
}
