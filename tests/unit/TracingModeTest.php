<?php

declare(strict_types=1);

namespace unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Flags;
use Solarwinds\ApmPhp\TracingMode;

#[CoversClass(TracingMode::class)]
class TracingModeTest extends TestCase
{
    public function test_tracing_mode_values(): void
    {
        $this->assertEquals(Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value, TracingMode::ALWAYS->value);
        $this->assertEquals(0x0, TracingMode::NEVER->value);
    }

    public function test_tracing_mode_enum_cases(): void
    {
        $this->assertInstanceOf(TracingMode::class, TracingMode::ALWAYS);
        $this->assertInstanceOf(TracingMode::class, TracingMode::NEVER);
    }
}
