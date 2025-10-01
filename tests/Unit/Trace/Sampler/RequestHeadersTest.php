<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\RequestHeaders;

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

    public function test_to_string(): void
    {
        $headers = new RequestHeaders();
        $this->assertEquals(
            'RequestHeaders(XTraceOptions=null, XTraceOptionsSignature=null)',
            (string) $headers
        );

        $headers = new RequestHeaders('foo', null);
        $this->assertEquals(
            'RequestHeaders(XTraceOptions=foo, XTraceOptionsSignature=null)',
            (string) $headers
        );

        $headers = new RequestHeaders(null, 'bar');
        $this->assertEquals(
            'RequestHeaders(XTraceOptions=null, XTraceOptionsSignature=bar)',
            (string) $headers
        );

        $headers = new RequestHeaders('foo', 'bar');
        $this->assertEquals(
            'RequestHeaders(XTraceOptions=foo, XTraceOptionsSignature=bar)',
            (string) $headers
        );
    }
}
