<?php

declare(strict_types=1);

namespace unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Flags;

#[CoversClass(Flags::class)]
class FlagsTest extends TestCase
{
    public function test_flags_values(): void
    {
        $this->assertEquals(0x0, Flags::OK->value);
        $this->assertEquals(0x1, Flags::INVALID->value);
        $this->assertEquals(0x2, Flags::OVERRIDE->value);
        $this->assertEquals(0x4, Flags::SAMPLE_START->value);
        $this->assertEquals(0x10, Flags::SAMPLE_THROUGH_ALWAYS->value);
        $this->assertEquals(0x20, Flags::TRIGGERED_TRACE->value);
    }

    public function test_flags_enum_cases(): void
    {
        $this->assertInstanceOf(Flags::class, Flags::OK);
        $this->assertInstanceOf(Flags::class, Flags::INVALID);
        $this->assertInstanceOf(Flags::class, Flags::OVERRIDE);
        $this->assertInstanceOf(Flags::class, Flags::SAMPLE_START);
        $this->assertInstanceOf(Flags::class, Flags::SAMPLE_THROUGH_ALWAYS);
        $this->assertInstanceOf(Flags::class, Flags::TRIGGERED_TRACE);
    }
}
