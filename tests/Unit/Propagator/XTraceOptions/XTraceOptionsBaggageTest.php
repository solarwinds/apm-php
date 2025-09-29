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
}
