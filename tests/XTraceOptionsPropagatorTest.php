<?php

declare(strict_types=1);

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\XTraceOptionsBaggage;
use Solarwinds\ApmPhp\XTraceOptionsPropagator;
use Solarwinds\ApmPhp\XTraceOptionsResponseBaggage;

#[CoversClass(XTraceOptionsPropagator::class)]
class XTraceOptionsPropagatorTest extends TestCase
{
    private TextMapPropagatorInterface $propagator;

    public function testFields(): void
    {
        $this->assertSame(XTraceOptionsPropagator::FIELDS, $this->propagator->fields());
    }

    public function testInjectEmptyBaggage(): void
    {
        $carrier = [];
        $this->propagator->inject($carrier);
        $this->assertEmpty($carrier);
    }

    public function testInjectXTraceOptionsResponseBaggage(): void
    {
        $carrier = [];
        $this->propagator->inject($carrier, null, Context::getCurrent()->withContextValue(XTraceOptionsResponseBaggage::getBuilder()->set('trigger-trace', 'ok')->set('foo', 'bar')->build()));
        $this->assertSame(['x-trace-options-response' => 'trigger-trace=ok;foo=bar'], $carrier);
    }

    public function testExtractEmptyBaggage(): void
    {
        $this->assertEquals(Context::getCurrent(), $this->propagator->extract([]));
    }

    public function testExtractXTraceOptionsBaggageOptionsOnly(): void
    {
        $carrier = [
            'x-trace-options' => 'foo',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue(!$bag->isEmpty());
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')->getValue());
        $this->assertNull($bag->getEntry('x-trace-options-signature'));
    }

    public function testExtractXTraceOptionsBaggageWithSignature(): void
    {
        $carrier = [
            'x-trace-options' => 'foo',
            'x-trace-options-signature' => 'bar',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue(!$bag->isEmpty());
        $this->assertEquals('foo', $bag->getEntry('x-trace-options')->getValue());
        $this->assertEquals('bar', $bag->getEntry('x-trace-options-signature')->getValue());
    }

    public function testExtractXTraceOptionsBaggageSignatureOnly(): void
    {
        $carrier = [
            'x-trace-options-signature' => 'bar',
        ];
        $context = $this->propagator->extract($carrier);
        $bag = XTraceOptionsBaggage::fromContext($context);
        $this->assertTrue($bag->isEmpty());
    }

    protected function setUp(): void
    {
        $this->propagator = XTraceOptionsPropagator::getInstance();
    }
}