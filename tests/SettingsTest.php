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
    public function test_merge_override_unset_respects_tracing_mode_never_and_trigger_mode_disabled(): void
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

    public function test_merge_override_unset_respects_tracing_mode_always_and_trigger_mode_enabled(): void
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

    public function test_merge_override_unset_defaults_to_remote_value_when_local_is_unset(): void
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

    public function test_merge_override_set_respects_tracing_mode_never_and_trigger_mode_disabled(): void
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

    public function test_merge_override_set_does_not_respect_tracing_mode_always_and_trigger_mode_enabled(): void
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

    public function test_merge_override_set_defaults_to_remote_value_when_local_is_unset(): void
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
