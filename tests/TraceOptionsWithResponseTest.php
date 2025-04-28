<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\TraceOptions;
use Solarwinds\ApmPhp\TraceOptionsResponse;
use Solarwinds\ApmPhp\TraceOptionsWithResponse;

#[CoversClass(TraceOptionsWithResponse::class)]
class TraceOptionsWithResponseTest extends TestCase
{
    public function test_trace_options_with_response_initialization(): void
    {
        $traceOptions = new TraceOptionsWithResponse(TraceOptions::from(''), new TraceOptionsResponse());
        $this->assertInstanceOf(TraceOptionsWithResponse::class, $traceOptions);
    }
}
