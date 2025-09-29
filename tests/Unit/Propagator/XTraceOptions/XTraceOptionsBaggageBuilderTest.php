<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Propagator\XTraceOptions;

use OpenTelemetry\API\Baggage\Metadata;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggageBuilder;

class XTraceOptionsBaggageBuilderTest extends TestCase
{
    public function test_set_and_build(): void
    {
        $builder = new XTraceOptionsBaggageBuilder();
        $builder->set('foo', 'bar');
        $baggage = $builder->build();
        $this->assertEquals('bar', $baggage->getValue('foo'));
    }

    public function test_remove(): void
    {
        $builder = new XTraceOptionsBaggageBuilder();
        $builder->set('foo', 'bar');
        $builder->remove('foo');
        $baggage = $builder->build();
        $this->assertNull($baggage->getValue('foo'));
    }

    public function test_set_empty_key_does_nothing(): void
    {
        $builder = new XTraceOptionsBaggageBuilder();
        $builder->set('', 'bar');
        $baggage = $builder->build();
        $this->assertNull($baggage->getValue(''));
    }

    public function test_set_with_metadata(): void
    {
        $builder = new XTraceOptionsBaggageBuilder();
        $metadata = new Metadata('meta');
        $builder->set('foo', 'bar', $metadata);
        $baggage = $builder->build();
        $entry = $baggage->getEntry('foo');
        $this->assertNotNull($entry);
        $this->assertEquals('meta', $entry->getMetadata()->getValue());
    }
}
