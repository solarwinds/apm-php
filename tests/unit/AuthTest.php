<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\Auth;

#[CoversClass(Auth::class)]
class AuthTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertEquals('ok', Auth::OK->value);
        $this->assertEquals('bad-timestamp', Auth::BAD_TIMESTAMP->value);
        $this->assertEquals('bad-signature', Auth::BAD_SIGNATURE->value);
        $this->assertEquals('no-signature-key', Auth::NO_SIGNATURE_KEY->value);
    }

    public function test_enum_keys(): void
    {
        $this->assertTrue(Auth::tryFrom('ok') === Auth::OK);
        $this->assertTrue(Auth::tryFrom('bad-timestamp') === Auth::BAD_TIMESTAMP);
        $this->assertTrue(Auth::tryFrom('bad-signature') === Auth::BAD_SIGNATURE);
        $this->assertTrue(Auth::tryFrom('no-signature-key') === Auth::NO_SIGNATURE_KEY);
    }
}
