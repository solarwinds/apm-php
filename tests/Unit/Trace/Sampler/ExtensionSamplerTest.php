<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\ExtensionSampler;

class ExtensionSamplerTest extends TestCase
{
    public function test_constructor_initializes_sampler(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = new ExtensionSampler($meterProvider, $config);
        $this->assertInstanceOf(ExtensionSampler::class, $sampler);
    }

    public function test_get_description_returns_expected_string(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = new ExtensionSampler($meterProvider, $config);
        $this->assertSame('Extension Sampler (apm_ext)', $sampler->getDescription());
    }
    //
    //    public function testShouldSampleReturnsSamplingResult(): void
    //    {
    //        $meterProvider = $this->createMock(MeterProviderInterface::class);
    //        $config = $this->createMock(Configuration::class);
    //        $sampler = new ExtensionSampler($meterProvider, $config);
    //        $parentContext = $this->createMock(ContextInterface::class);
    //        $attributes = $this->createMock(AttributesInterface::class);
    //        $result = $sampler->shouldSample(
    //            $parentContext,
    //            'traceid',
    //            'spanName',
    //            0,
    //            $attributes,
    //            []
    //        );
    //        $this->assertInstanceOf(SamplingResult::class, $result);
    //    }
    //
    //    public function testRequestHandlesExtensionNotLoaded(): void
    //    {
    //        $meterProvider = $this->createMock(MeterProviderInterface::class);
    //        $config = $this->createMock(Configuration::class);
    //        $sampler = new ExtensionSampler($meterProvider, $config);
    //        $reflection = new \ReflectionClass($sampler);
    //        $method = $reflection->getMethod('request');
    //        $method->setAccessible(true);
    //        $method->invoke($sampler);
    //    }
}
