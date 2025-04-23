<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\LocalSettings;
use Solarwinds\ApmPhp\TracingMode;

#[CoversClass(LocalSettings::class)]
class LocalSettingsTest extends TestCase
{
    public function testLocalSettingsInitialization(): void
    {
        $tracingMode = TracingMode::ALWAYS;
        $triggerMode = true;
        $localSettings = new LocalSettings($tracingMode, $triggerMode);

        $this->assertEquals($tracingMode, $localSettings->getTracingMode());
        $this->assertTrue($localSettings->getTriggerMode());
    }

    public function testLocalSettingsInitializationWithNullTracingMode(): void
    {
        $tracingMode = null;
        $triggerMode = false;
        $localSettings = new LocalSettings($tracingMode, $triggerMode);

        $this->assertNull($localSettings->getTracingMode());
        $this->assertFalse($localSettings->getTriggerMode());
    }

    public function testSetAndGetTracingMode(): void
    {
        $localSettings = new LocalSettings(null, true);
        $localSettings->setTracingMode(TracingMode::NEVER);

        $this->assertEquals(TracingMode::NEVER, $localSettings->getTracingMode());
    }

    public function testSetAndGetTriggerMode(): void
    {
        $localSettings = new LocalSettings(TracingMode::ALWAYS, false);
        $localSettings->setTriggerMode(true);

        $this->assertTrue($localSettings->getTriggerMode());
    }
}
