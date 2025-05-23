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
}
