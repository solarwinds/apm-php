<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\RequestHeaders;
use Solarwinds\ApmPhp\Trace\Sampler\SampleState;
use Solarwinds\ApmPhp\Trace\Sampler\Settings;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptionsWithResponse;

class SampleStateTest extends TestCase
{
    public function test_constructor_and_properties()
    {
        $attributes = Attributes::create(['foo' => 'bar']);
        $settings = $this->createMock(Settings::class);
        $headers = $this->createMock(RequestHeaders::class);
        $traceOptions = $this->createMock(TraceOptionsWithResponse::class);
        $state = new SampleState(1, $attributes, $settings, 'ts', $headers, $traceOptions);
        $this->assertSame(1, $state->decision);
        $this->assertSame($attributes, $state->attributes);
        $this->assertSame($settings, $state->settings);
        $this->assertSame('ts', $state->traceState);
        $this->assertSame($headers, $state->headers);
        $this->assertSame($traceOptions, $state->traceOptions);
    }

    public function test_to_string_with_all_values()
    {
        $attributes = Attributes::create(['foo' => 'bar']);
        $settings = $this->createMock(Settings::class);
        $settings->method('__toString')->willReturn('settings_str');
        $headers = $this->createMock(RequestHeaders::class);
        $headers->method('__toString')->willReturn('headers_str');
        $traceOptions = $this->createMock(TraceOptionsWithResponse::class);
        $traceOptions->method('__toString')->willReturn('traceOptions_str');
        $state = new SampleState(2, $attributes, $settings, 'state', $headers, $traceOptions);
        $str = (string) $state;
        $this->assertStringContainsString('decision=2', $str);
        $this->assertStringContainsString('bar', $str);
        $this->assertStringContainsString('settings_str', $str);
        $this->assertStringContainsString('state', $str);
        $this->assertStringContainsString('headers_str', $str);
        $this->assertStringContainsString('traceOptions_str', $str);
    }

    public function test_to_string_with_nulls()
    {
        $attributes = Attributes::create(['foo' => 'bar']);
        $headers = $this->createMock(RequestHeaders::class);
        $headers->method('__toString')->willReturn('headers_str');
        $state = new SampleState(0, $attributes, null, null, $headers, null);
        $str = (string) $state;
        $this->assertStringContainsString('decision=0', $str);
        $this->assertStringContainsString('null', $str);
        $this->assertStringContainsString('headers_str', $str);
    }
}
