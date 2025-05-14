<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SemConv\TraceAttributes;

class TransactionNameSpanProcessor extends NoopSpanProcessor implements SpanProcessorInterface
{
    use LogsMessagesTrait;
    public const TRANSACTION_NAME_ATTRIBUTE = 'sw.transaction';
    private const TRANSACTION_NAME_POOL_TTL = 60; // 1 minute
    private const TRANSACTION_NAME_POOL_MAX = 200;
    private const TRANSACTION_NAME_MAX_LENGTH = 256;
    private const TRANSACTION_NAME_DEFAULT = 'other';
    private TransactionNamePool $pool;
    private static ?SpanProcessorInterface $instance = null;
    public function __construct()
    {
        $this->pool = new TransactionNamePool(
            self::TRANSACTION_NAME_POOL_MAX,
            self::TRANSACTION_NAME_POOL_TTL,
            self::TRANSACTION_NAME_MAX_LENGTH,
            self::TRANSACTION_NAME_DEFAULT
        );
    }
    public static function getInstance(): SpanProcessorInterface
    {
        if (null === self::$instance) {
            self::$instance = new self();
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
        $http_route = $span->getAttribute(TraceAttributes::HTTP_ROUTE);
        $this->logDebug('Name from http.route: ' . ($http_route !== null ? $http_route : 'null'));
        $url_path = $span->getAttribute(TraceAttributes::URL_PATH);
        $this->logDebug('Name from url.path: ' . ($url_path !== null ? $url_path : 'null'));
        $span_name = $span->getName();
        $this->logDebug('Name from span: ' . $span_name);
        if ($http_route !== null) {
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
}
