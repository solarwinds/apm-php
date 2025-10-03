<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Propagator\XTraceOptions;

use OpenTelemetry\API\Baggage\Entry;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggage;

class XTraceOptionsBaggageTest extends TestCase
{
    public function test_get_builder_and_build(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $this->assertInstanceOf(XTraceOptionsBaggage::class, $baggage);
        $this->assertEquals('bar', $baggage->getValue('foo'));
    }

    public function test_get_empty(): void
    {
        $empty = XTraceOptionsBaggage::getEmpty();
        $this->assertInstanceOf(XTraceOptionsBaggage::class, $empty);
        $this->assertTrue($empty->isEmpty());
    }

    public function test_from_context_and_get_current(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $context = Context::getCurrent()->withContextValue($baggage);
        $fromContext = XTraceOptionsBaggage::fromContext($context);
        $this->assertEquals('bar', $fromContext->getValue('foo'));
    }

    public function test_get_entry_and_get_value(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $entry = $baggage->getEntry('foo');
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertEquals('bar', $entry->getValue());
    }

    public function test_is_empty(): void
    {
        $empty = XTraceOptionsBaggage::getEmpty();
        $this->assertTrue($empty->isEmpty());
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $this->assertFalse($baggage->isEmpty());
    }

    public function test_activate_sets_baggage_in_context(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $scope = $baggage->activate();
        $this->assertInstanceOf(\OpenTelemetry\Context\ScopeInterface::class, $scope);
        $current = XTraceOptionsBaggage::getCurrent();
        $this->assertEquals('bar', $current->getValue('foo'));
        $scope->detach(); // Clean up
    }

    public function test_get_all_yields_all_entries(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $builder->set('baz', 'qux');
        $baggage = $builder->build();
        $count = 0;
        $all = [];
        foreach ($baggage->getAll() as $key => $value) {
            $all[$key] = $value;
        }
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('foo', $all);
        $this->assertArrayHasKey('baz', $all);
        $this->assertEquals('bar', $all['foo']->getValue());
        $this->assertEquals('qux', $all['baz']->getValue());
    }

    public function test_get_entry_and_get_value_missing_key(): void
    {
        $baggage = XTraceOptionsBaggage::getEmpty();
        $this->assertNull($baggage->getEntry('notfound'));
        $this->assertNull($baggage->getValue('notfound'));
    }

    public function test_to_builder_returns_builder_with_same_entries(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $newBuilder = $baggage->toBuilder();
        $newBaggage = $newBuilder->build();
        $this->assertEquals('bar', $newBaggage->getValue('foo'));
    }

    public function test_store_in_context_and_from_context(): void
    {
        $builder = XTraceOptionsBaggage::getBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $context = Context::getCurrent();
        $newContext = $baggage->storeInContext($context);
        $fromContext = XTraceOptionsBaggage::fromContext($newContext);
        $this->assertEquals('bar', $fromContext->getValue('foo'));
    }

    public function test_from_context_returns_empty_when_no_baggage(): void
    {
        $context = Context::getCurrent();
        $baggage = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue($baggage->isEmpty());
    }
}
