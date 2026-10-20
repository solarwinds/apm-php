<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SemConv\TraceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;

class ResponseTimeSpanProcessor extends NoopSpanProcessor implements SpanProcessorInterface
{
    use LogsMessagesTrait;
    private HistogramInterface $histogram;
    public const TRANSACTION_NAME_ATTRIBUTE = 'sw.transaction';
    private const TRANSACTION_NAME_POOL_TTL = 60; // 1 minute
    private const TRANSACTION_NAME_POOL_MAX = 200;
    private const TRANSACTION_NAME_MAX_LENGTH = 256;
    private const TRANSACTION_NAME_DEFAULT = 'other';
    private TransactionNamePool $pool;
    private static ?SpanProcessorInterface $instance = null;
    private ?string $envTransactionName;

    public function __construct(?MeterProviderInterface $meterProvider = null, ?string $name = null)
    {
        $meterProvider = $meterProvider ?? Globals::meterProvider();
        $meter = $meterProvider->getMeter('sw.apm.request.metrics');
        $this->histogram = $meter->createHistogram(
            'trace.service.response_time',
            'ms',
            'Duration of each entry span for the service, typically meaning the time taken to process an inbound request.'
        );

        $this->envTransactionName = Configuration::has(SolarwindsEnv::SW_APM_TRANSACTION_NAME) ? Configuration::getString(SolarwindsEnv::SW_APM_TRANSACTION_NAME) : $name ?? null;

        $this->pool = new TransactionNamePool(
            self::TRANSACTION_NAME_POOL_MAX,
            self::TRANSACTION_NAME_POOL_TTL,
            self::TRANSACTION_NAME_MAX_LENGTH,
            self::TRANSACTION_NAME_DEFAULT
        );
    }

    public static function getInstance(?MeterProviderInterface $meterProvider = null, ?string $name = null): SpanProcessorInterface
    {
        if (null === self::$instance) {
            self::$instance = new self($meterProvider, $name);
        }

        return self::$instance;
    }

    public function getTransactionNamePool(): TransactionNamePool
    {
        return $this->pool;
    }

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        $parentSpanContext = $span->getParentContext();
        if ($parentSpanContext->isValid() && !$parentSpanContext->isRemote()) {
            return;
        }
        $this->logDebug('Transaction name from SW_APM_TRANSACTION_NAME env: ' . ($this->envTransactionName !== null ? $this->envTransactionName : 'null'));
        $http_route = $span->getAttribute(TraceAttributes::HTTP_ROUTE);
        $this->logDebug('Name from http.route: ' . ($http_route !== null ? $http_route : 'null'));
        $url_path = $span->getAttribute(TraceAttributes::URL_PATH);
        $this->logDebug('Name from url.path: ' . ($url_path !== null ? $url_path : 'null'));
        $span_name = $span->getName();
        $this->logDebug('Name from span: ' . $span_name);
        if ($this->envTransactionName !== null) {
            $name = $this->envTransactionName;
        } elseif ($http_route !== null) {
            $name = $http_route;
        } elseif ($url_path !== null) {
            $name = TransactionNameUtil::resolveTransactionName($url_path);
        } else {
            $name = $span_name;
        }
        $name = $this->pool->register($name);
        $this->logDebug('Final transaction name ' . $name);
        $span->setAttribute(self::TRANSACTION_NAME_ATTRIBUTE, $name);
    }

    /**
     * Still need the deprecated class constant
     * @phan-suppress PhanDeprecatedClassConstant
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
        $copy = [ResponseTimeSpanProcessor::TRANSACTION_NAME_ATTRIBUTE];
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
