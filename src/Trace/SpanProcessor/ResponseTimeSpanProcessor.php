<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SemConv\TraceAttributes;

class ResponseTimeSpanProcessor extends NoopSpanProcessor implements SpanProcessorInterface
{
    use LogsMessagesTrait;
    private HistogramInterface $histogram;

    public function __construct(?MeterProviderInterface $meterProvider = null)
    {
        $meterProvider = $meterProvider ?? Globals::meterProvider();
        $meter = $meterProvider->getMeter('sw.apm.request.metrics');
        $this->histogram = $meter->createHistogram(
            'trace.service.response_time',
            'ms',
            'Duration of each entry span for the service, typically meaning the time taken to process an inbound request.'
        );
    }

    /**
     * Still need the deprecated class constant
     * @phan-suppress PhanDeprecatedClassConstant
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function onEnd(ReadableSpanInterface $span): void
    {
        $parentSpanContext = $span->getParentContext();
        if ($parentSpanContext->isValid() && !$parentSpanContext->isRemote()) {
            return;
        }
        $durationMs = $span->getDuration() / 1000_000; // ns converts to ms
        $spanData = $span->toSpanData();
        $attributes = [
            'sw.is_error' => $spanData->getStatus()->getCode() === StatusCode::STATUS_ERROR,
        ];
        $copy = [TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE];
        if ($span->getKind() === SpanKind::KIND_SERVER) {
            $copy = array_merge($copy, [
                TraceAttributes::HTTP_REQUEST_METHOD,
                TraceAttributes::HTTP_RESPONSE_STATUS_CODE,
                TraceAttributes::HTTP_METHOD,
                TraceAttributes::HTTP_STATUS_CODE,
            ]);
        }
        foreach ($copy as $key) {
            $value = $span->getAttribute($key);
            if ($value !== null) {
                $attributes[$key] = $value;
            }
        }
        $this->logDebug('Recording response time: ' . $durationMs . 'ms, ' . json_encode($attributes));
        $this->histogram->record($durationMs, $attributes);
    }
}
