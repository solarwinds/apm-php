<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\ResponseHeaders;

#[CoversClass(ResponseHeaders::class)]
class ResponseHeadersTest extends TestCase
{
    public function test_default_values(): void
    {
        $headers = new ResponseHeaders();
        $this->assertNull($headers->XTraceOptionsResponse);
    }

    public function test_set_values(): void
    {
        $headers = new ResponseHeaders();
        $headers->XTraceOptionsResponse = 'trace-options-response';

        $this->assertEquals('trace-options-response', $headers->XTraceOptionsResponse);
    }
}
