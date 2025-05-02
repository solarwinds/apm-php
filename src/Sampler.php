<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SemConv\TraceAttributes;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggage;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsPropagator;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsResponseBaggage;

/**
 * Still need the deprecated class constant
 * @phan-suppress PhanDeprecatedClassConstant
 */
function httpSpanMetadata(int $kind, AttributesInterface $attributes): array
{
    if (
        $kind !== SpanKind::KIND_SERVER ||
        !$attributes->has(TraceAttributes::HTTP_REQUEST_METHOD) && !$attributes->has(TraceAttributes::HTTP_METHOD)
    ) {
        return ['http' => false];
    }

    $method = $attributes->get(TraceAttributes::HTTP_REQUEST_METHOD) ?? $attributes->get(TraceAttributes::HTTP_METHOD);
    $status = $attributes->get(TraceAttributes::HTTP_RESPONSE_STATUS_CODE) ?? $attributes->get(TraceAttributes::HTTP_STATUS_CODE) ?? 0;
    $scheme = $attributes->get(TraceAttributes::URL_SCHEME) ?? $attributes->get(TraceAttributes::HTTP_SCHEME) ?? 'http';
    $hostname = $attributes->get(TraceAttributes::SERVER_ADDRESS) ?? $attributes->get(TraceAttributes::NET_HOST_NAME) ?? 'localhost';
    $path = $attributes->get(TraceAttributes::URL_PATH) ?? $attributes->get(TraceAttributes::HTTP_TARGET);
    $url = "{$scheme}://{$hostname}{$path}";

    return [
        'http' => true,
        'method' => $method,
        'status' => $status,
        'scheme' => $scheme,
        'hostname' => $hostname,
        'path' => $path,
        'url' => $url,
    ];
}

function parseSettings(mixed $unparsed): ?array
{
    if (!is_array($unparsed)) {
        return null;
    }

    if (
        isset($unparsed['value'], $unparsed['timestamp'], $unparsed['ttl']) &&
        is_numeric($unparsed['value']) &&
        is_numeric($unparsed['timestamp']) &&
        is_numeric($unparsed['ttl'])
    ) {
        $sampleRate = (int) $unparsed['value'];
        $timestamp = (int) $unparsed['timestamp'];
        $ttl = (int) $unparsed['ttl'];
    } else {
        return null;
    }

    $flags = Flags::OK->value;
    if (isset($unparsed['flags']) && is_string($unparsed['flags'])) {
        foreach (explode(',', $unparsed['flags']) as $flag) {
            switch ($flag) {
                case 'OVERRIDE':
                    $flags |= Flags::OVERRIDE->value;

                    break;
                case 'SAMPLE_START':
                    $flags |= Flags::SAMPLE_START->value;

                    break;
                case 'SAMPLE_THROUGH_ALWAYS':
                    $flags |= Flags::SAMPLE_THROUGH_ALWAYS->value;

                    break;
                case 'TRIGGER_TRACE':
                    $flags |= Flags::TRIGGERED_TRACE->value;

                    break;

            }
        }
    } else {
        return null;
    }

    $buckets = [];
    $signatureKey = null;
    if (isset($unparsed['arguments']) && is_array($unparsed['arguments'])) {
        if (
            isset($unparsed['arguments']['BucketCapacity'], $unparsed['arguments']['BucketRate']) &&
            is_numeric($unparsed['arguments']['BucketCapacity']) &&
            is_numeric($unparsed['arguments']['BucketRate'])
        ) {
            $buckets[BucketType::DEFAULT->value] = new BucketSettings((float) $unparsed['arguments']['BucketCapacity'], (float) $unparsed['arguments']['BucketRate']);
        }

        if (
            isset($unparsed['arguments']['TriggerRelaxedBucketCapacity'], $unparsed['arguments']['TriggerRelaxedBucketRate']) &&
            is_numeric($unparsed['arguments']['TriggerRelaxedBucketCapacity']) &&
            is_numeric($unparsed['arguments']['TriggerRelaxedBucketRate'])
        ) {
            $buckets[BucketType::TRIGGER_RELAXED->value] = new BucketSettings((float) $unparsed['arguments']['TriggerRelaxedBucketCapacity'], (float) $unparsed['arguments']['TriggerRelaxedBucketRate']);
        }

        if (
            isset($unparsed['arguments']['TriggerStrictBucketCapacity'], $unparsed['arguments']['TriggerStrictBucketRate']) &&
            is_numeric($unparsed['arguments']['TriggerStrictBucketCapacity']) &&
            is_numeric($unparsed['arguments']['TriggerStrictBucketRate'])
        ) {
            $buckets[BucketType::TRIGGER_STRICT->value] = new BucketSettings((float) $unparsed['arguments']['TriggerStrictBucketCapacity'], (float) $unparsed['arguments']['TriggerStrictBucketRate']);
        }

        if (isset($unparsed['arguments']['SignatureKey']) && is_string($unparsed['arguments']['SignatureKey'])) {
            $signatureKey = $unparsed['arguments']['SignatureKey'];
        }
    }

    $warning = $unparsed['warning'] ?? null;

    return [
        'settings' => new Settings(
            $sampleRate,
            SampleSource::Remote,
            $flags,
            $buckets,
            $signatureKey,
            $timestamp,
            $ttl
        ),
        'warning' => $warning,
    ];
}

abstract class Sampler extends OboeSampler
{
    use LogsMessagesTrait;

    private ?TracingMode $tracingMode;
    private bool $triggerMode;
    private array $transactionSettings = [];

    private CompletedFuture $ready;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, mixed $initial = null)
    {
        parent::__construct($meterProvider);

        $this->tracingMode = $config->getTracingMode() !== null
            ? ($config->getTracingMode() ? TracingMode::ALWAYS : TracingMode::NEVER)
            : null;

        $this->triggerMode = $config->isTriggerTraceEnabled();

        foreach ($config->getTransactionSettings() as $transactionSetting) {
            $this->transactionSettings[] = new TransactionSetting($transactionSetting['tracing'] ?? false, $transactionSetting['matcher'] ?? fn () => false);
        }

        $this->ready = new CompletedFuture(false);

        if ($initial) {
            $this->parsedAndUpdateSettings($initial);
        }
    }

    protected function parsedAndUpdateSettings(mixed $settings): ?Settings
    {
        $parsed = parseSettings($settings);
        if (isset($parsed['settings'])) {
            $this->logDebug('Valid settings ' . $parsed['settings']);
            parent::updateSettings($parsed['settings']);
            if (!$this->ready->await()) {
                $this->ready->map(static fn (): bool => true);
            }

            //            if (!empty($parsed['warning'])) {
            //                // $this->logger->warn($parsed['warning']);
            //            }
            return $parsed['settings'];
        }

        //            $this->logger->debug('Invalid settings', $settings);
        return null;

    }

    public function waitUntilReady(int $timeout): bool
    {
        while (!$this->ready->await()) {
            $timeout -= 5;
            if ($timeout <= 0) {
                break;
            }
            usleep(5 * 1000);
        }

        return $this->ready->await();
    }

    public function localSettings(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): LocalSettings {
        $settings = new LocalSettings($this->tracingMode, $this->triggerMode);

        if ($this->transactionSettings === []) {
            return $settings;
        }

        $meta = httpSpanMetadata($spanKind, $attributes);
        $identifier = $meta['http'] ? $meta['url'] : $spanKind . ':' . $spanName;

        foreach ($this->transactionSettings as $transactionSetting) {
            if (is_a($transactionSetting, TransactionSetting::class) && $transactionSetting->getMatcher()($identifier)) {
                $settings->setTracingMode($transactionSetting->getTracing() ? TracingMode::ALWAYS : TracingMode::NEVER);

                break;
            }
        }

        return $settings;
    }

    public function requestHeaders(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): RequestHeaders {
        $xTraceOptionsBaggage = XTraceOptionsBaggage::fromContext($parentContext);
        if (!$xTraceOptionsBaggage->isEmpty()) {
            $xTraceOptions = $xTraceOptionsBaggage->getValue(XTraceOptionsPropagator::XTRACEOPTIONS);
            $xTraceOptionsSignature = $xTraceOptionsBaggage->getValue(XTraceOptionsPropagator::XTRACEOPTIONSSIGNATURE);
            if (null === $xTraceOptions || is_string($xTraceOptions) && null === $xTraceOptionsSignature || is_string($xTraceOptionsSignature)) {
                return new RequestHeaders($xTraceOptions, $xTraceOptionsSignature);
            }
        }

        return new RequestHeaders();
    }

    public function setResponseHeaders(
        ResponseHeaders $headers,
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): ?TraceState {
        // To do: check if the header is set in context
        $xTraceOptionsResponseBaggageBuilder = XTraceOptionsResponseBaggage::getBuilder();
        $xTraceOptionsResponseBaggage = $xTraceOptionsResponseBaggageBuilder->set(XTraceOptionsPropagator::XTRACEOPTIONSRESPONSE, $headers->XTraceOptionsResponse)->build();
        $xTraceOptionsResponseBaggage->storeInContext(Context::getCurrent());

        return null;
    }
}
