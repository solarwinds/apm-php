<?php

declare(strict_types=1);

namespace unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\RequestHeaders;

#[CoversClass(RequestHeaders::class)]
class RequestHeadersTest extends TestCase
{
    public function test_default_values(): void
    {
        $headers = new RequestHeaders();
        $this->assertNull($headers->XTraceOptions);
        $this->assertNull($headers->XTraceOptionsSignature);
    }

    public function test_set_values(): void
    {
        $headers = new RequestHeaders();
        $headers->XTraceOptions = 'trace-options';
        $headers->XTraceOptionsSignature = 'trace-options-signature';

        $this->assertEquals('trace-options', $headers->XTraceOptions);
        $this->assertEquals('trace-options-signature', $headers->XTraceOptionsSignature);
    }
}
