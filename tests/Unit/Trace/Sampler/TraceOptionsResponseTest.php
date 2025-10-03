<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\Auth;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptionsResponse;
use Solarwinds\ApmPhp\Trace\Sampler\TriggerTrace;

#[CoversClass(TraceOptionsResponse::class)]
class TraceOptionsResponseTest extends TestCase
{
    public function test_basic(): void
    {
        $response = new TraceOptionsResponse();
        $response->auth = Auth::OK;
        $response->triggerTrace = TriggerTrace::OK;
        $expected = 'auth=ok;trigger-trace=ok';
        $this->assertEquals($expected, (string) $response);
    }

    public function test_ignored_values(): void
    {
        $response = new TraceOptionsResponse();
        $response->auth = Auth::OK;
        $response->triggerTrace = TriggerTrace::TRIGGER_TRACING_DISABLED;
        $response->ignored = ['invalid-key1', 'invalid_key2'];
        $expected = 'auth=ok;trigger-trace=trigger-tracing-disabled;ignored=invalid-key1,invalid_key2';
        $this->assertEquals($expected, (string) $response);
    }

    public function test_all_null(): void
    {
        $response = new TraceOptionsResponse();
        $this->assertSame('', (string) $response);
    }

    public function test_only_auth(): void
    {
        $response = new TraceOptionsResponse();
        $response->auth = Auth::OK;
        $this->assertSame('auth=ok', (string) $response);
    }

    public function test_only_trigger_trace(): void
    {
        $response = new TraceOptionsResponse();
        $response->triggerTrace = TriggerTrace::TRIGGER_TRACING_DISABLED;
        $this->assertSame('trigger-trace=trigger-tracing-disabled', (string) $response);
    }

    public function test_only_ignored_empty(): void
    {
        $response = new TraceOptionsResponse();
        $response->ignored = [];
        $this->assertSame('ignored=', (string) $response);
    }

    public function test_only_ignored_one_value(): void
    {
        $response = new TraceOptionsResponse();
        $response->ignored = ['foo'];
        $this->assertSame('ignored=foo', (string) $response);
    }

    public function test_ignored_with_non_string_values(): void
    {
        $response = new TraceOptionsResponse();
        $response->ignored = [123, null, 'bar'];
        $this->assertSame('ignored=123,,bar', (string) $response);
    }

    public function test_other_enum_values(): void
    {
        $response = new TraceOptionsResponse();
        $response->auth = Auth::BAD_TIMESTAMP;
        $response->triggerTrace = TriggerTrace::NOT_REQUESTED;
        $this->assertSame('auth=bad-timestamp;trigger-trace=not-requested', (string) $response);

        $response->auth = Auth::BAD_SIGNATURE;
        $response->triggerTrace = TriggerTrace::IGNORED;
        $this->assertSame('auth=bad-signature;trigger-trace=ignored', (string) $response);

        $response->auth = Auth::NO_SIGNATURE_KEY;
        $response->triggerTrace = TriggerTrace::TRACING_DISABLED;
        $this->assertSame('auth=no-signature-key;trigger-trace=tracing-disabled', (string) $response);

        $response->triggerTrace = TriggerTrace::TRIGGER_TRACING_DISABLED;
        $this->assertSame('auth=no-signature-key;trigger-trace=trigger-tracing-disabled', (string) $response);

        $response->triggerTrace = TriggerTrace::RATE_EXCEEDED;
        $this->assertSame('auth=no-signature-key;trigger-trace=rate-exceeded', (string) $response);

        $response->triggerTrace = TriggerTrace::SETTINGS_NOT_AVAILABLE;
        $this->assertSame('auth=no-signature-key;trigger-trace=settings-not-available', (string) $response);
    }
}
