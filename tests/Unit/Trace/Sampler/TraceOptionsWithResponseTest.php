<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptions;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptionsResponse;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptionsWithResponse;

#[CoversClass(TraceOptionsWithResponse::class)]
class TraceOptionsWithResponseTest extends TestCase
{
    public function test_trace_options_with_response_initialization(): void
    {
        $traceOptions = new TraceOptionsWithResponse(TraceOptions::from(''), new TraceOptionsResponse());
        $this->assertInstanceOf(TraceOptionsWithResponse::class, $traceOptions);
    }

    public function test_properties_are_copied_from_options(): void
    {
        $options = new TraceOptions(true, 123, 'sw', ['custom-key' => 'val'], [['ignored', 'x']]);
        $response = new TraceOptionsResponse();
        $obj = new TraceOptionsWithResponse($options, $response);
        $this->assertTrue($obj->triggerTrace);
        $this->assertSame(123, $obj->timestamp);
        $this->assertSame('sw', $obj->swKeys);
        $this->assertSame(['custom-key' => 'val'], $obj->custom);
        $this->assertSame([['ignored', 'x']], $obj->ignored);
        $this->assertSame($response, $obj->response);
    }

    public function test_to_string_all_default(): void
    {
        $obj = new TraceOptionsWithResponse(new TraceOptions(), new TraceOptionsResponse());
        $str = (string) $obj;
        $this->assertStringNotContainsString('trigger-trace=', $str);
        $this->assertStringNotContainsString('ts=', $str);
        $this->assertStringNotContainsString('sw-keys=', $str);
        $this->assertStringContainsString('custom=', $str);
        $this->assertStringContainsString('ignored=', $str);
    }

    public function test_to_string_all_set(): void
    {
        $options = new TraceOptions(true, 42, 'sw', ['custom-key' => 'val'], [['foo', 'bar']]);
        $response = new TraceOptionsResponse();
        $response->auth = \Solarwinds\ApmPhp\Trace\Sampler\Auth::OK;
        $response->triggerTrace = \Solarwinds\ApmPhp\Trace\Sampler\TriggerTrace::OK;
        $response->ignored = ['bad'];
        $obj = new TraceOptionsWithResponse($options, $response);
        $str = (string) $obj;
        $this->assertStringContainsString('trigger-trace=true', $str);
        $this->assertStringContainsString('ts=42', $str);
        $this->assertStringContainsString('sw-keys=sw', $str);
        $this->assertStringContainsString('custom=custom-key=val', $str);
        $this->assertStringContainsString('ignored=foo=bar', $str);
        $this->assertStringContainsString('auth=ok', $str);
        $this->assertStringContainsString('trigger-trace=ok', $str);
        $this->assertStringContainsString('ignored=bad', $str);
    }

    public function test_to_string_mixed_values(): void
    {
        $options = new TraceOptions(null, 99, null, [], [['x', 'y']]);
        $response = new TraceOptionsResponse();
        $response->auth = \Solarwinds\ApmPhp\Trace\Sampler\Auth::BAD_SIGNATURE;
        $obj = new TraceOptionsWithResponse($options, $response);
        $str = (string) $obj;
        $this->assertStringContainsString('ts=99', $str);
        $this->assertStringContainsString('ignored=x=y', $str);
        $this->assertStringContainsString('auth=bad-signature', $str);
    }
}
