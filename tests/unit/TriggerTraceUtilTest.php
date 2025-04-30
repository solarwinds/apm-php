<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Auth;
use Solarwinds\ApmPhp\TriggerTraceUtil;

#[CoversClass(TriggerTraceUtil::class)]
class TriggerTraceUtilTest extends TestCase
{
    public function test_valid_signature(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59c',
            '8mZ98ZnZhhggcsUmdMbS',
            time() - 60
        );

        $this->assertEquals(Auth::OK, $result);
    }

    public function test_invalid_signature(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59d',
            '8mZ98ZnZhhggcsUmdMbS',
            time() - 60
        );

        $this->assertEquals(Auth::BAD_SIGNATURE, $result);
    }

    public function test_missing_signature_key(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59c',
            null,
            time() - 60
        );

        $this->assertEquals(Auth::NO_SIGNATURE_KEY, $result);
    }

    public function test_timestamp_past(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59c',
            '8mZ98ZnZhhggcsUmdMbS',
            time() - 10 * 60
        );

        $this->assertEquals(Auth::BAD_TIMESTAMP, $result);
    }

    public function test_timestamp_future(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59c',
            '8mZ98ZnZhhggcsUmdMbS',
            time() + 10 * 60
        );

        $this->assertEquals(Auth::BAD_TIMESTAMP, $result);
    }

    public function test_missing_timestamp(): void
    {
        $result = TriggerTraceUtil::validateSignature(
            'trigger-trace;pd-keys=lo:se,check-id:123;ts=1564597681',
            '2c1c398c3e6be898f47f74bf74f035903b48b59c',
            '8mZ98ZnZhhggcsUmdMbS',
            null
        );

        $this->assertEquals(Auth::BAD_TIMESTAMP, $result);
    }
}
