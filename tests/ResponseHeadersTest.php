<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\ResponseHeaders;

#[CoversClass(ResponseHeaders::class)]
class ResponseHeadersTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $headers = new ResponseHeaders();
        $this->assertNull($headers->XTraceOptionsResponse);
    }

    public function testSetValues(): void
    {
        $headers = new ResponseHeaders();
        $headers->XTraceOptionsResponse = 'trace-options-response';

        $this->assertEquals('trace-options-response', $headers->XTraceOptionsResponse);
    }
}
