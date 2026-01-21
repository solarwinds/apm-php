<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
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

    public function test_should_sample_extension_not_loaded(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = $this->getMockBuilder(ExtensionSampler::class)->setConstructorArgs([$meterProvider, $config])->onlyMethods(['isExtensionLoaded', 'settingsFunction'])->getMock();
        $sampler->expects($this->once())->method('isExtensionLoaded')->willReturn(false);
        $sampler->expects($this->never())->method('settingsFunction');
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_should_sample_extension_loaded_but_no_settings(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = $this->getMockBuilder(ExtensionSampler::class)->setConstructorArgs([$meterProvider, $config])->onlyMethods(['isExtensionLoaded', 'settingsFunction'])->getMock();
        $sampler->expects($this->once())->method('isExtensionLoaded')->willReturn(true);
        $sampler->expects($this->once())->method('settingsFunction')->willReturn('');
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }

    public function test_should_sample_extension_loaded_valid_settings(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = $this->getMockBuilder(ExtensionSampler::class)->setConstructorArgs([$meterProvider, $config])->onlyMethods(['isExtensionLoaded', 'settingsFunction'])->getMock();
        $sampler->expects($this->once())->method('isExtensionLoaded')->willReturn(true);
        $sampler->expects($this->once())->method('settingsFunction')->willReturn('{"value":1000000,"flags":"SAMPLE_START,SAMPLE_THROUGH_ALWAYS,SAMPLE_BUCKET_ENABLED,TRIGGER_TRACE","timestamp":4072744812,"ttl":120,"arguments":{"BucketCapacity":2,"BucketRate":1,"TriggerRelaxedBucketCapacity":20,"TriggerRelaxedBucketRate":1,"TriggerStrictBucketCapacity":6,"TriggerStrictBucketRate":0.1,"SignatureKey":"a9012f2c6b25d1f5d8b87ed1a3858abd230cac7c99e8ec2aeacfaba6aa123456"}}');
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(2, $result->getDecision());
    }

    public function test_should_sample_extension_loaded_invalid_settings(): void
    {
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $config = $this->createMock(Configuration::class);
        $sampler = $this->getMockBuilder(ExtensionSampler::class)->setConstructorArgs([$meterProvider, $config])->onlyMethods(['isExtensionLoaded', 'settingsFunction'])->getMock();
        $sampler->expects($this->once())->method('isExtensionLoaded')->willReturn(true);
        $sampler->expects($this->once())->method('settingsFunction')->willReturn('{"value":1000000,"flags":"SAMPLE_START,SAMPLE_THROUGH_ALWAYS,SAMPLE_BUCKET_ENABLED,TRIGGER_TRACE","timestamp":4072744812,"ttl":120,"arguments":{"BucketCapacity":2,"BucketRate":1,"TriggerRelaxedBucketCapacity":20,"TriggerRelaxedBucketRate":1,"TriggerStrictBucketCapacity":6,"TriggerStrictBucketRate":0.1,"SignatureKey":"signaturekey"}');
        $result = $sampler->shouldSample($this->createMock(\OpenTelemetry\Context\ContextInterface::class), '', '', 0, $this->createMock(\OpenTelemetry\SDK\Common\Attribute\AttributesInterface::class), []);
        $this->assertEquals(0, $result->getDecision());
    }
}
