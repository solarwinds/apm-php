<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\TriggerTrace;

#[CoversClass(TriggerTrace::class)]
class TriggerTraceTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertEquals('ok', TriggerTrace::OK->value);
        $this->assertEquals('not-requested', TriggerTrace::NOT_REQUESTED->value);
        $this->assertEquals('ignored', TriggerTrace::IGNORED->value);
        $this->assertEquals('tracing-disabled', TriggerTrace::TRACING_DISABLED->value);
        $this->assertEquals('trigger-tracing-disabled', TriggerTrace::TRIGGER_TRACING_DISABLED->value);
        $this->assertEquals('rate-exceeded', TriggerTrace::RATE_EXCEEDED->value);
        $this->assertEquals('settings-not-available', TriggerTrace::SETTINGS_NOT_AVAILABLE->value);
    }

    public function test_enum_keys(): void
    {
        $this->assertTrue(TriggerTrace::tryFrom('ok') === TriggerTrace::OK);
        $this->assertTrue(TriggerTrace::tryFrom('not-requested') === TriggerTrace::NOT_REQUESTED);
        $this->assertTrue(TriggerTrace::tryFrom('ignored') === TriggerTrace::IGNORED);
        $this->assertTrue(TriggerTrace::tryFrom('tracing-disabled') === TriggerTrace::TRACING_DISABLED);
        $this->assertTrue(TriggerTrace::tryFrom('trigger-tracing-disabled') === TriggerTrace::TRIGGER_TRACING_DISABLED);
        $this->assertTrue(TriggerTrace::tryFrom('rate-exceeded') === TriggerTrace::RATE_EXCEEDED);
        $this->assertTrue(TriggerTrace::tryFrom('settings-not-available') === TriggerTrace::SETTINGS_NOT_AVAILABLE);
    }
}
