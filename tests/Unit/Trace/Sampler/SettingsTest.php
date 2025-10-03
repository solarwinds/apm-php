<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\TracingMode;
use Solarwinds\ApmPhp\Trace\Sampler\Flags;
use Solarwinds\ApmPhp\Trace\Sampler\LocalSettings;
use Solarwinds\ApmPhp\Trace\Sampler\SampleSource;
use Solarwinds\ApmPhp\Trace\Sampler\Settings;

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

    public function test_constructor_assigns_properties_correctly(): void
    {
        $settings = new Settings(
            42,
            SampleSource::Remote,
            0xABCD,
            ['bucket1', 'bucket2'],
            'sigKey',
            1234567890,
            99
        );
        $this->assertSame(42, $settings->sampleRate);
        $this->assertSame(SampleSource::Remote, $settings->sampleSource);
        $this->assertSame(0xABCD, $settings->flags);
        $this->assertSame(['bucket1', 'bucket2'], $settings->buckets);
        $this->assertSame('sigKey', $settings->signatureKey);
        $this->assertSame(1234567890, $settings->timestamp);
        $this->assertSame(99, $settings->ttl);
    }

    public function test_to_string_returns_valid_json(): void
    {
        $settings = new Settings(
            7,
            SampleSource::LocalDefault,
            0x1234,
            ['a', 'b'],
            null,
            111,
            222
        );
        $json = (string) $settings;
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertSame(7, $data['sampleRate']);
        $this->assertSame(SampleSource::LocalDefault->value, $data['sampleSource']);
        $this->assertSame(0x1234, $data['flags']);
        $this->assertSame(['a', 'b'], $data['buckets']);
        $this->assertNull($data['signatureKey']);
        $this->assertSame(111, $data['timestamp']);
        $this->assertSame(222, $data['ttl']);
    }

    public function test_to_string_with_all_fields(): void
    {
        $settings = new Settings(
            0,
            SampleSource::Remote,
            0,
            [],
            '',
            0,
            0
        );
        $json = (string) $settings;
        $data = json_decode($json, true);
        $this->assertSame('', $data['signatureKey']);
        $this->assertSame([], $data['buckets']);
    }

    public function test_merge_with_all_flags_and_edge_cases(): void
    {
        $remote = new Settings(
            100,
            SampleSource::Remote,
            Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value | Flags::OVERRIDE->value,
            ['bucketX'],
            'edge',
            999,
            1
        );
        $local = new LocalSettings(null, false);
        $merged = Settings::merge($remote, $local);
        // TRIGGERED_TRACE should be unset due to local setting
        $this->assertSame($remote->flags&~Flags::TRIGGERED_TRACE->value, $merged->flags);
        $this->assertSame($remote->buckets, $merged->buckets);
        $this->assertSame($remote->signatureKey, $merged->signatureKey);
    }
}
