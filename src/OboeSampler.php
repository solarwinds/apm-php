<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use OpenTelemetry\SDK\Trace\Span;

const SW_KEYS_ATTRIBUTE = 'SWKeys';
const PARENT_ID_ATTRIBUTE = 'sw.tracestate_parent_id';
const SAMPLE_RATE_ATTRIBUTE = 'SampleRate';
const SAMPLE_SOURCE_ATTRIBUTE = 'SampleSource';
const BUCKET_CAPACITY_ATTRIBUTE = 'BucketCapacity';
const BUCKET_RATE_ATTRIBUTE = 'BucketRate';
const TRIGGERED_TRACE_ATTRIBUTE = 'TriggeredTrace';

enum SpanType: int
{
    case ROOT = 0;
    case ENTRY = 1;
    case LOCAL = 2;
}

abstract class OboeSampler implements SamplerInterface
{
    use LogsMessagesTrait;

    private readonly Counters $counters;
    private array $buckets;
    private ?Settings $settings = null;

    public function __construct(?MeterProviderInterface $meterProvider = null)
    {
        $this->counters = new Counters($meterProvider);
        $this->buckets = [
            BucketType::DEFAULT->value => new TokenBucket(),
            BucketType::TRIGGER_RELAXED->value => new TokenBucket(),
            BucketType::TRIGGER_STRICT->value => new TokenBucket(),
        ];
    }

    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        $parentSpan = Span::fromContext($parentContext);
        $s = new SampleState(
            SamplingResult::DROP,
            $attributes,
            $this->getSettings($parentContext, $traceId, $spanName, $spanKind, $attributes, $links),
            $parentSpan->getContext()->getTraceState()?->get('sw'),
            $this->requestHeaders($parentContext, $traceId, $spanName, $spanKind, $attributes, $links),
            null
        );
        $this->counters->getRequestCount()->add(1, [], $parentContext);
        if ($s->headers->XTraceOptions !== null) {
            $parsed = TraceOptions::from($s->headers->XTraceOptions);
            $s->traceOptions = new TraceOptionsWithResponse($parsed, new TraceOptionsResponse());
            $this->logDebug('X-Trace-Options present ' . $s->traceOptions);
            if ($s->headers->XTraceOptionsSignature !== null) {
                $this->logDebug('X-Trace-Options-Signature present; validating');
                $s->traceOptions->response->auth = TriggerTraceUtil::validateSignature(
                    $s->headers->XTraceOptions,
                    $s->headers->XTraceOptionsSignature,
                    $s->settings?->signatureKey,
                    $s->traceOptions->timestamp
                );
                if ($s->traceOptions->response->auth !== Auth::OK) {
                    $this->logDebug('X-Trace-Options-Signature invalid; tracing disabled');
                    $new_trace_state = $this->setResponseHeadersFromSampleState($s, $parentContext, $traceId, $spanName, $spanKind, $attributes, $links);

                    return new SamplingResult(SamplingResult::DROP, $attributes, $new_trace_state);
                }
            }
            if (!$s->traceOptions->triggerTrace) {
                $s->traceOptions->response->triggerTrace = TriggerTrace::NOT_REQUESTED;
            }
            if ($s->traceOptions->swKeys !== null) {
                $swKeyAttributes = Attributes::create([
                    SW_KEYS_ATTRIBUTE => $s->traceOptions->swKeys,
                ]);
                $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $swKeyAttributes);
            }
            $customAttributes = Attributes::create($s->traceOptions->custom);
            $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $customAttributes);
            if ($s->traceOptions->ignored !== []) {
                $s->traceOptions->response->ignored = array_map(fn ($item) => is_array($item) && count($item) > 0? $item[0] : '', $s->traceOptions->ignored);
            }
        }
        if (!$s->settings) {
            $this->logDebug('settings unavailable; sampling disabled');
            if ($s->traceOptions && $s->traceOptions->triggerTrace) {
                $this->logDebug('trigger trace requested but unavailable');
                $s->traceOptions->response->triggerTrace = TriggerTrace::SETTINGS_NOT_AVAILABLE;
            }
            $new_trace_state = $this->setResponseHeadersFromSampleState($s, $parentContext, $traceId, $spanName, $spanKind, $attributes, $links);

            return new SamplingResult(SamplingResult::DROP, $s->attributes, $new_trace_state);
        }
        if ($s->traceState !== null && preg_match('/^[0-9a-f]{16}-[0-9a-f]{2}$/', $s->traceState)) {
            $this->logDebug('context is valid for parent-based sampling');
            $this->parentBasedAlgo($s, $parentContext);
        } elseif ($s->settings->flags & Flags::SAMPLE_START->value) {
            if ($s->traceOptions?->triggerTrace) {
                $this->logDebug('trigger trace requested');
                $this->triggerTraceAlgo($s, $parentContext);
            } else {
                $this->logDebug('defaulting to dice roll');
                $this->diceRollAlgo($s, $parentContext);
            }
        } else {
            $this->logDebug('SAMPLE_START is unset; sampling disabled');
            $this->disabledAlgo($s);
        }
        // $this->logDebug("final sampling state ".$s);
        $new_trace_state = $this->setResponseHeadersFromSampleState($s, $parentContext, $traceId, $spanName, $spanKind, $attributes, $links);

        return new SamplingResult($s->decision, $s->attributes, $new_trace_state);
    }

    private function getSettings(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): ?Settings {
        if (!$this->settings) {
            return null;
        }
        if (time() > $this->settings->timestamp + $this->settings->ttl) {
            $this->logDebug('settings expired, removing');
            $this->settings = null;

            return null;
        }

        return Settings::merge($this->settings, $this->localSettings($parentContext, $traceId, $spanName, $spanKind, $attributes, $links));
    }

    abstract public function localSettings(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): LocalSettings;

    abstract public function requestHeaders(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): RequestHeaders;

    private function setResponseHeadersFromSampleState(
        SampleState $s,
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): ?TraceState {
        $headers = new ResponseHeaders();
        if ($s->traceOptions) {
            $headers->XTraceOptionsResponse = (string) $s->traceOptions->response;
        }

        return $this->setResponseHeaders($headers, $parentContext, $traceId, $spanName, $spanKind, $attributes, $links);
    }

    abstract public function setResponseHeaders(
        ResponseHeaders $headers,
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): ?TraceState;

    private function parentBasedAlgo(SampleState $s, ContextInterface $parentContext): void
    {
        $parentIdAttributes = Attributes::create([
            PARENT_ID_ATTRIBUTE => substr((string) $s->traceState, 0, 16),
        ]);
        $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $parentIdAttributes);

        if ($s->traceOptions && $s->traceOptions->triggerTrace) {
            $this->logDebug('trigger trace requested but ignored');
            $s->traceOptions->response->triggerTrace = TriggerTrace::IGNORED;
        }

        if ($s->settings !== null && $s->settings->flags & Flags::SAMPLE_THROUGH_ALWAYS->value) {
            $this->logDebug('SAMPLE_THROUGH_ALWAYS is set; parent-based sampling');
            $flags = hexdec(substr((string) $s->traceState, -2));
            $sampled = $flags & TraceFlags::SAMPLED;
            if ($sampled) {
                $this->logDebug('parent is sampled; record and sample');
                $this->counters->getTraceCount()->add(1, [], $parentContext);
                $this->counters->getThroughTraceCount()->add(1, [], $parentContext);
                $s->decision = SamplingResult::RECORD_AND_SAMPLE;
            } else {
                $this->logDebug('parent is not sampled; record only');
                $s->decision = SamplingResult::RECORD_ONLY;
            }
        } else {
            $this->logDebug('SAMPLE_THROUGH_ALWAYS is unset; sampling disabled');
            if ($s->settings !== null && $s->settings->flags & Flags::SAMPLE_START->value) {
                $this->logDebug('SAMPLE_START is set; record');
                $s->decision = SamplingResult::RECORD_ONLY;
            } else {
                $this->logDebug("SAMPLE_START is unset; don't record");
                $s->decision = SamplingResult::DROP;
            }
        }
    }

    private function triggerTraceAlgo(SampleState $s, ContextInterface $parentContext): void
    {
        if ($s->settings !== null && $s->settings->flags & Flags::TRIGGERED_TRACE->value) {
            $this->logDebug('TRIGGERED_TRACE set; trigger tracing');
            $bucket = $s->traceOptions?->response->auth ? $this->buckets[BucketType::TRIGGER_RELAXED->value] : $this->buckets[BucketType::TRIGGER_STRICT->value];
            $newAttributes = Attributes::create([
                TRIGGERED_TRACE_ATTRIBUTE => true,
                BUCKET_CAPACITY_ATTRIBUTE => $bucket->getCapacity(),
                BUCKET_RATE_ATTRIBUTE => $bucket->getRate(),
            ]);
            $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $newAttributes);

            if ($bucket->consume()) {
                $this->logDebug('sufficient capacity; record and sample');
                $this->counters->getTriggeredTraceCount()->add(1, [], $parentContext);
                $this->counters->getTraceCount()->add(1, [], $parentContext);

                if ($s->traceOptions) {
                    $s->traceOptions->response->triggerTrace = TriggerTrace::OK;
                }
                $s->decision = SamplingResult::RECORD_AND_SAMPLE;
            } else {
                $this->logDebug('insufficient capacity; record only');
                if ($s->traceOptions) {
                    $s->traceOptions->response->triggerTrace = TriggerTrace::RATE_EXCEEDED;
                }
                $s->decision = SamplingResult::RECORD_ONLY;
            }
        } else {
            $this->logDebug('TRIGGERED_TRACE unset; record only');
            if ($s->traceOptions) {
                $s->traceOptions->response->triggerTrace = TriggerTrace::TRIGGER_TRACING_DISABLED;
            }
            $s->decision = SamplingResult::RECORD_ONLY;
        }
    }

    private function diceRollAlgo(SampleState $s, ContextInterface $parentContext): void
    {
        $dice = new Dice(1000000, $s->settings? $s->settings->sampleRate : 0);
        $sampleAttributes = Attributes::create([
            SAMPLE_RATE_ATTRIBUTE => $dice->getRate(),
            SAMPLE_SOURCE_ATTRIBUTE => $s->settings? $s->settings->sampleSource->value : SampleSource::LocalDefault,
        ]);
        $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $sampleAttributes);
        $this->counters->getSampleCount()->add(1, [], $parentContext);
        if ($dice->roll()) {
            $this->logDebug('dice roll success; checking capacity');
            $bucket = $this->buckets[BucketType::DEFAULT->value];
            $bucketAttributes = Attributes::create([
                BUCKET_CAPACITY_ATTRIBUTE => $bucket->getCapacity(),
                BUCKET_RATE_ATTRIBUTE => $bucket->getRate(),
            ]);
            $s->attributes = Attributes::factory()->builder()->merge($s->attributes, $bucketAttributes);
            if ($bucket->consume()) {
                $this->logDebug('sufficient capacity; record and sample');
                $this->counters->getTraceCount()->add(1, [], $parentContext);
                $s->decision = SamplingResult::RECORD_AND_SAMPLE;
            } else {
                $this->logDebug('insufficient capacity; record only');
                $this->counters->getTokenBucketExhaustionCount()->add(1, [], $parentContext);
                $s->decision = SamplingResult::RECORD_ONLY;
            }
        } else {
            $this->logDebug('dice roll failure; record only');
            $s->decision = SamplingResult::RECORD_ONLY;
        }
    }

    private function disabledAlgo(SampleState $s): void
    {
        if ($s->traceOptions && $s->traceOptions->triggerTrace) {
            $this->logDebug('trigger trace requested but tracing disabled');
            $s->traceOptions->response->triggerTrace = TriggerTrace::TRACING_DISABLED;
        }
        if ($s->settings && $s->settings->flags & Flags::SAMPLE_THROUGH_ALWAYS->value) {
            $this->logDebug('SAMPLE_THROUGH_ALWAYS is set; record');
            $s->decision = SamplingResult::RECORD_ONLY;
        } else {
            $this->logDebug("SAMPLE_THROUGH_ALWAYS is unset; don't record");
            $s->decision = SamplingResult::DROP;
        }
    }

    public function getDescription(): string
    {
        return 'OboeSampler';
    }

    protected function updateSettings(Settings $settings): void
    {
        if ($settings->timestamp > ($this->settings?->timestamp ?? 0)) {
            $this->settings = $settings;
            foreach ($this->buckets as $type => $bucket) {
                $bucketSettings = $this->settings->buckets[$type] ?? null;
                if ($bucketSettings !== null && is_a($bucketSettings, BucketSettings::class)) {
                    $bucket->update($bucketSettings->capacity, $bucketSettings->rate);
                }
            }
        }
    }
}
