<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Flags;
use Solarwinds\ApmPhp\LocalSettings;
use Solarwinds\ApmPhp\SampleSource;
use Solarwinds\ApmPhp\Settings;
use Solarwinds\ApmPhp\TracingMode;

#[CoversClass(Settings::class)]
class SettingsTest extends TestCase
{
    public function testMergeOverrideUnsetRespectsTracingModeNeverAndTriggerModeDisabled(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(TracingMode::NEVER, false);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals(0x0, $merged->flags);
    }

    public function testMergeOverrideUnsetRespectsTracingModeAlwaysAndTriggerModeEnabled(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            0x0,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(TracingMode::ALWAYS, true);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals(
            Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value,
            $merged->flags
        );
    }

    public function testMergeOverrideUnsetDefaultsToRemoteValueWhenLocalIsUnset(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(null, true);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals($remote, $merged);
    }

    public function testMergeOverrideSetRespectsTracingModeNeverAndTriggerModeDisabled(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            Flags::OVERRIDE->value | Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(TracingMode::NEVER, false);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals(Flags::OVERRIDE->value, $merged->flags);
    }

    public function testMergeOverrideSetDoesNotRespectTracingModeAlwaysAndTriggerModeEnabled(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            Flags::OVERRIDE->value,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(TracingMode::ALWAYS, true);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals($remote, $merged);
    }

    public function testMergeOverrideSetDefaultsToRemoteValueWhenLocalIsUnset(): void
    {
        $remote = new Settings(
            1,
            SampleSource::LocalDefault,
            Flags::OVERRIDE->value,
            [],
            null,
            time(),
            60
        );
        $local = new LocalSettings(null, false);

        $merged = Settings::merge($remote, $local);
        $this->assertEquals($remote, $merged);
    }
}
