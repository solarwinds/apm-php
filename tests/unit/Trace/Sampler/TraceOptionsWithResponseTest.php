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
}
