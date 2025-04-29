<?php

declare(strict_types=1);

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\NonRecordingSpan;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\Data\Sum;
use OpenTelemetry\SDK\Metrics\MeterProviderBuilder;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Trace\RandomIdGenerator;
use OpenTelemetry\SDK\Trace\SamplingResult;
use OpenTelemetry\SDK\Trace\Span;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\BucketSettings;
use Solarwinds\ApmPhp\BucketType;
use Solarwinds\ApmPhp\Flags;
use Solarwinds\ApmPhp\LocalSettings;
use Solarwinds\ApmPhp\OboeSampler;
use Solarwinds\ApmPhp\RequestHeaders;
use Solarwinds\ApmPhp\ResponseHeaders;
use Solarwinds\ApmPhp\SampleSource;
use Solarwinds\ApmPhp\Settings;

class TestOboeSampler extends OboeSampler
{
    private readonly LocalSettings $localSettings;
    private readonly RequestHeaders $requestHeaders;
    private ?ResponseHeaders $responseHeaders = null;

    public function __construct(?MeterProviderInterface $meterProvider, ?Settings $settings, LocalSettings $localSettings, RequestHeaders $requestHeaders)
    {
        parent::__construct($meterProvider);
        $this->localSettings = $localSettings;
        $this->requestHeaders = $requestHeaders;
        if ($settings !== null) {
            $this->updateSettings($settings);
        }
    }

    public function getResponseHeaders(): ?ResponseHeaders
    {
        return $this->responseHeaders;
    }

    #[Override]
    public function setResponseHeaders(ResponseHeaders $headers, ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): ?TraceState
    {
        $this->responseHeaders = $headers;

        return null;
    }

    #[Override]
    public function localSettings(ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): LocalSettings
    {
        return $this->localSettings;
    }

    #[Override]
    public function requestHeaders(ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): RequestHeaders
    {
        return $this->requestHeaders;
    }

}

#[CoversClass(OboeSampler::class)]
class OboeSamplerTest extends TestCase
{
    public function test_description(): void
    {
        $sampler = new TestOboeSampler(null, null, new LocalSettings(null, true), $this->makeRequestHeaders([]));
        $this->assertEquals('OboeSampler', $sampler->getDescription());
    }

    private function makeRequestHeaders(array $options): RequestHeaders
    {
        $optionsTriggerTrace = $options['triggerTrace'] ?? null;
        $optionsKvs = $options['kvs'] ?? null;
        $optionsSignature = $options['signature'] ?? null;
        $optionsSignatureKey = $options['signatureKey'] ?? null;

        if ($optionsTriggerTrace === null && $optionsKvs === null && $optionsSignature === null) {
            return new RequestHeaders();
        }

        $timestamp = time();
        if ($optionsSignature === 'bad-timestamp') {
            $timestamp -= 10 * 60;
        }
        $ts = 'ts=' . $timestamp;
        $triggerTrace = is_bool($optionsTriggerTrace) && $optionsTriggerTrace ? 'trigger-trace' : null;
        $kvs = is_array($optionsKvs) ? array_map(fn ($k, $v) => "$k=$v", array_keys($optionsKvs), $optionsKvs) : [];
        $headers = new RequestHeaders(implode(';', array_filter(array_merge([$triggerTrace], $kvs, [$ts]))));

        if ($optionsSignature !== null) {
            $optionsSignatureKey ??= bin2hex(random_bytes(8));
            $headers->XTraceOptionsSignature = hash_hmac('sha1', (string) $headers->XTraceOptions, (string) $optionsSignatureKey);
        }

        return $headers;
    }

    public function test_invalid_x_trace_options_signature_rejects_missing_signature_key(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                1000000,
                SampleSource::Remote,
                Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'signature' => true,
                'kvs' => ['custom-key' => 'value'],
            ])
        );
        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testInvalidXTraceOptionsSignatureRejectsMissingSignatureKey',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $this->assertEmpty($sample->getAttributes());
        $this->assertStringContainsString('auth=no-signature-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    /**
     * @param null|string|true $sw
     *
     * @psalm-param 'inverse'|null|true $sw
     */
    private function createParentContext(?string $traceId, ?string $spanId, bool $sampled, bool $isRemote, string|bool|null $sw): ContextInterface
    {
        $generator = new RandomIdGenerator();
        $traceId = $traceId ?? $generator->generateTraceId();
        $spanId = $spanId ?? $generator->generateSpanId();
        $traceFlag = $sampled ? TraceFlags::SAMPLED : TraceFlags::DEFAULT;
        $swFlags = $sw === 'inverse' ? ($sampled ? '00' : '01') : ($sampled ? '01' : '00');
        if ($isRemote) {
            $spanContext = SpanContext::createFromRemoteParent(
                $traceId,
                $spanId,
                $traceFlag,
                $sw ? new TraceState('sw=' . $spanId . '-' . $swFlags) : null
            );
        } else {
            $spanContext = SpanContext::create(
                $traceId,
                $spanId,
                $traceFlag,
                $sw ? new TraceState('sw=' . $spanId . '-' . $swFlags) : null
            );
        }

        return Context::getCurrent()->withContextValue(new NonRecordingSpan($spanContext));
    }

    private function sumMetricsToMap(array $metrics): array
    {
        $map = [];
        foreach ($metrics as $metric) {
            if ($metric->data instanceof Sum) {
                foreach ($metric->data->dataPoints as $dataPoint) {
                    $map[$metric->name] ??= $dataPoint->value;
                }
            }
        }

        return $map;
    }

    public function test_invalid_x_trace_options_signature_rejects_bad_timestamp(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                1000000,
                SampleSource::Remote,
                Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                'key',
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'signature' => 'bad-timestamp',
                'signatureKey' => 'key',
                'kvs' => ['custom-key' => 'value'],
            ])
        );
        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testInvalidXTraceOptionsSignatureRejectsBadTimestamp',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $this->assertEmpty($sample->getAttributes());
        $this->assertStringContainsString('auth=bad-timestamp', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_invalid_x_trace_options_signature_rejects_bad_signature(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                1000000,
                SampleSource::Remote,
                Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                'key1',
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'signature' => true,
                'signatureKey' => 'key2',
                'kvs' => ['custom-key' => 'value'],
            ])
        );
        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testInvalidXTraceOptionsSignatureRejectsBadSignature',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $this->assertEmpty($sample->getAttributes());
        $this->assertStringContainsString('auth=bad-signature', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_missing_settings_does_not_sample(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            null,
            new LocalSettings(null, false),
            new RequestHeaders()
        );
        $parentContext = Context::getCurrent();
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testMissingSettingsDoesNotSample',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_missing_settings_expires_after_ttl(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time() - 60,
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );
        $parentContext = $this->createParentContext(null, null, true, true, true);
        sleep(10);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testMissingSettingsExpiresAfterTtl',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_missing_settings_respects_x_trace_options_keys_and_values(): void
    {
        $sampler = new TestOboeSampler(
            null,
            null,
            new LocalSettings(null, false),
            $this->makeRequestHeaders([
                'kvs' => ['custom-key' => 'value', 'sw-keys' => 'sw-values'],
            ])
        );
        $parentContext = Context::getCurrent();
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testMissingSettingsRespectsXTraceOptionsKeysAndValues',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals('sw-values', $attributes['SWKeys'] ?? '');
        $this->assertStringContainsString('trigger-trace=not-requested', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_missing_settings_ignores_trigger_trace(): void
    {
        $sampler = new TestOboeSampler(
            null,
            null,
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
            ])
        );
        $parentContext = Context::getCurrent();
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testMissingSettingsIgnoresTriggerTrace',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            [],
        );
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertStringContainsString('trigger-trace=settings-not-available', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_x_trace_options_respects_keys_and_values(): void
    {
        $sampler = new TestOboeSampler(
            null,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            $this->makeRequestHeaders([
                'kvs' => ['custom-key' => 'value', 'sw-keys' => 'sw-values'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testXTraceOptionsRespectsKeysAndValues',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals('sw-values', $attributes['SWKeys'] ?? '');
        $this->assertStringContainsString('trigger-trace=not-requested', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_x_trace_options_ignores_trigger_trace(): void
    {
        $sampler = new TestOboeSampler(
            null,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testXTraceOptionsIgnoresTriggerTrace',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertStringContainsString('trigger-trace=ignored', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_sample_through_always_set_respects_parent_sampled(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysSetRespectsParentSampled',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertEquals(SamplingResult::RECORD_AND_SAMPLE, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(Span::fromContext($parentContext)->getContext()->getSpanId(), $attributes['sw.tracestate_parent_id'] ?? '');
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tracecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.through_trace_count'] ?? 0);
    }

    public function test_sample_through_always_set_respects_parent_not_sampled(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, false, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysSetRespectsParentNotSampled',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(Span::fromContext($parentContext)->getContext()->getSpanId(), $attributes['sw.tracestate_parent_id'] ?? '');
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_sample_through_always_set_respects_sw_sampled_over_w3c_not_sampled(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, false, true, 'inverse');
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysSetRespectsSwSampledOverW3cNotSampled',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_AND_SAMPLE, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(Span::fromContext($parentContext)->getContext()->getSpanId(), $attributes['sw.tracestate_parent_id'] ?? '');
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tracecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.through_trace_count'] ?? 0);
    }

    public function test_sample_through_always_set_respects_sw_not_sampled_over_w3c_sampled(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, 'inverse');
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysSetRespectsSwNotSampledOverW3cSampled',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(Span::fromContext($parentContext)->getContext()->getSpanId(), $attributes['sw.tracestate_parent_id'] ?? '');
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_sample_through_always_unset_records_but_does_not_sample_when_sample_start_set(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysUnsetRecordsButDoesNotSampleWhenSampleStartSet',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_sample_through_always_unset_does_not_record_or_sample_when_sample_start_unset(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                0x0,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, true);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleThroughAlwaysUnsetDoesNotRecordOrSampleWhenSampleStartUnset',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());
        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_trigger_trace_requested_triggered_trace_set_unsigned_records_and_samples_when_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value | Flags::TRIGGERED_TRACE->value,
                [
                    BucketType::TRIGGER_STRICT->value => new BucketSettings(10, 5),
                    BucketType::TRIGGER_RELAXED->value => new BucketSettings(0, 0),
                ],
                null,
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'sw-keys' => 'sw-values'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testTriggeredTraceSetUnsignedRecordsAndSamplesWhenCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_AND_SAMPLE, $sample->getDecision());

        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals('sw-values', $attributes['SWKeys'] ?? '');
        $this->assertEquals(10, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(5, $attributes['BucketRate'] ?? -1);
        $this->assertStringContainsString('trigger-trace=ok', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tracecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.triggered_trace_count'] ?? 0);
    }

    public function test_trigger_trace_requested_triggered_trace_set_unsigned_records_but_no_sample_when_no_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value | Flags::TRIGGERED_TRACE->value,
                [
                    BucketType::TRIGGER_STRICT->value => new BucketSettings(0, 0),
                    BucketType::TRIGGER_RELAXED->value => new BucketSettings(20, 10),
                ],
                null,
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
            ])
        );
        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testTriggerTraceRequestedTriggeredTraceSetUnsignedRecordsButNoSampleWhenNoCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals(0, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(0, $attributes['BucketRate'] ?? -1);
        $this->assertStringContainsString('trigger-trace=rate-exceeded', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_trigger_trace_requested_triggered_trace_set_signed_records_and_samples_when_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value | Flags::TRIGGERED_TRACE->value,
                [
                    BucketType::TRIGGER_STRICT->value => new BucketSettings(0, 0),
                    BucketType::TRIGGER_RELAXED->value => new BucketSettings(20, 10),
                ],
                'key',
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'sw-keys' => 'sw-values'],
                'signature' => true,
                'signatureKey' => 'key',
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testTriggerTraceRequestedTriggeredTraceSetSignedRecordsAndSamplesWhenCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_AND_SAMPLE, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals('sw-values', $attributes['SWKeys'] ?? '');
        $this->assertEquals(20, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(10, $attributes['BucketRate'] ?? -1);
        $this->assertStringContainsString('auth=ok', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('trigger-trace=ok', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tracecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.triggered_trace_count'] ?? 0);
    }

    public function test_trigger_trace_requested_triggered_trace_set_signed_records_but_no_sample_when_no_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value | Flags::TRIGGERED_TRACE->value,
                [
                    BucketType::TRIGGER_STRICT->value => new BucketSettings(10, 5),
                    BucketType::TRIGGER_RELAXED->value => new BucketSettings(0, 0),
                ],
                'key',
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
                'signature' => true,
                'signatureKey' => 'key',
            ])
        );
        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testTriggerTraceRequestedTriggeredTraceSetSignedRecordsButNoSampleWhenNoCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals(0, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(0, $attributes['BucketRate'] ?? -1);
        $this->assertStringContainsString('trigger-trace=rate-exceeded', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_trigger_trace_requested_triggered_trace_unset_records_but_no_sample(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testTriggerTraceRequestedTriggeredTraceUnsetRecordsButNoSample',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertStringContainsString('trigger-trace=trigger-tracing-disabled', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_dice_roll_respects_x_trace_options_keys_and_values(): void
    {
        $sampler = new TestOboeSampler(
            null,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            $this->makeRequestHeaders([
                'kvs' => ['custom-key' => 'value', 'sw-keys' => 'sw-values'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, false, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testDiceRollRespectsXTraceOptionsKeysAndValues',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );

        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertEquals('sw-values', $attributes['SWKeys'] ?? '');
        $this->assertStringContainsString('trigger-trace=not-requested', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_dice_roll_records_and_samples_when_dice_success_and_sufficient_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                1000000,
                SampleSource::Remote,
                Flags::SAMPLE_START->value,
                [BucketType::DEFAULT->value => new BucketSettings(10, 5)],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, false, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testDiceRollRecordsAndSamplesWhenDiceSuccessAndSufficientCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_AND_SAMPLE, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(1000000, $attributes['SampleRate'] ?? -1);
        $this->assertEquals(6, $attributes['SampleSource'] ?? -1);
        $this->assertEquals(10, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(5, $attributes['BucketRate'] ?? -1);

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.samplecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tracecount'] ?? 0);
    }

    public function test_dice_roll_records_but_does_not_sample_when_dice_success_but_insufficient_capacity(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                1000000,
                SampleSource::Remote,
                Flags::SAMPLE_START->value,
                [BucketType::DEFAULT->value => new BucketSettings(0, 0)],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, false, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testDiceRollRecordsButDoesNotSampleWhenDiceSuccessButInsufficientCapacity',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(1000000, $attributes['SampleRate'] ?? -1);
        $this->assertEquals(6, $attributes['SampleSource'] ?? -1);
        $this->assertEquals(0, $attributes['BucketCapacity'] ?? -1);
        $this->assertEquals(0, $attributes['BucketRate'] ?? -1);

        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.samplecount'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.tokenbucket_exhaustion_count'] ?? 0);
    }

    public function test_dice_roll_records_but_does_not_sample_when_dice_failure(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_START->value,
                [BucketType::DEFAULT->value => new BucketSettings(10, 5)],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, false, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testDiceRollRecordsButDoesNotSampleWhenDiceFailure',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals(0, $attributes['SampleRate'] ?? -1);
        $this->assertEquals(2, $attributes['SampleSource'] ?? -1);
        $this->assertArrayNotHasKey('BucketCapacity', $attributes ?? []);
        $this->assertArrayNotHasKey('BucketRate', $attributes ?? []);
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
        $this->assertSame(1, $metrics['trace.service.samplecount'] ?? 0);
    }

    public function test_sample_start_unset_ignores_trigger_trace(): void
    {
        $sampler = new TestOboeSampler(
            null,
            new Settings(
                0,
                SampleSource::LocalDefault,
                0x0,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, true),
            $this->makeRequestHeaders([
                'triggerTrace' => true,
                'kvs' => ['custom-key' => 'value', 'invalid-key' => 'value'],
            ])
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleStartUnsetIgnoresTriggerTrace',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );

        $attributes = null;
        foreach ($sample->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        $this->assertEquals('value', $attributes['custom-key'] ?? '');
        $this->assertStringContainsString('trigger-trace=tracing-disabled', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
        $this->assertStringContainsString('ignored=invalid-key', strval($sampler->getResponseHeaders()?->XTraceOptionsResponse));
    }

    public function test_sample_start_unset_records_when_sample_through_always_set(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                Flags::SAMPLE_THROUGH_ALWAYS->value,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleStartUnsetRecordsWhenSampleThroughAlwaysSet',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::RECORD_ONLY, $sample->getDecision());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }

    public function test_sample_start_unset_does_not_record_when_sample_through_always_unset(): void
    {
        $exporter = new InMemoryExporter();
        $reader = new ExportingReader($exporter);
        $meterProvider = (new MeterProviderBuilder())->addReader($reader)->build();
        $sampler = new TestOboeSampler(
            $meterProvider,
            new Settings(
                0,
                SampleSource::LocalDefault,
                0x0,
                [],
                null,
                time(),
                10
            ),
            new LocalSettings(null, false),
            new RequestHeaders()
        );

        $parentContext = $this->createParentContext(null, null, true, true, null);
        $sample = $sampler->shouldSample(
            $parentContext,
            Span::fromContext($parentContext)->getContext()->getTraceId(),
            'testSampleStartUnsetDoesNotRecordWhenSampleThroughAlwaysUnset',
            SpanKind::KIND_INTERNAL,
            Attributes::create([]),
            []
        );
        $reader->collect();
        $metrics = $this->sumMetricsToMap($exporter->Collect());

        $this->assertEquals(SamplingResult::DROP, $sample->getDecision());
        $this->assertSame(1, $metrics['trace.service.request_count'] ?? 0);
    }
}
