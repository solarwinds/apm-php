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
    private const TRANSACTION_NAME_ATTRIBUTE = 'sw.transaction';
    private const TRANSACTION_NAME_POOL_TTL = 60; // 1 minute
    private const TRANSACTION_NAME_POOL_MAX = 200;
    private const TRANSACTION_NAME_MAX_LENGTH = 256;
    private const TRANSACTION_NAME_DEFAULT = 'other';
    private TransactionNamePool $pool;
    public function __construct()
    {
        $this->pool = new TransactionNamePool(
            self::TRANSACTION_NAME_POOL_MAX,
            self::TRANSACTION_NAME_POOL_TTL,
            self::TRANSACTION_NAME_MAX_LENGTH,
            self::TRANSACTION_NAME_DEFAULT
        );
    }
    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        $parentContext = $span->getParentContext();
        if ($parentContext->isValid() && !$parentContext->isRemote()) {
            return;
        }
        $htt_route = $span->getAttribute(TraceAttributes::HTTP_ROUTE);
        $url_path = $span->getAttribute(TraceAttributes::URL_PATH);
        if ($htt_route) {
            $name = $htt_route;
        } elseif ($url_path) {
            $name = TransactionNameUtil::resolveTransactionName($url_path);
        } else {
            $name = $span->getName();
        }
        $name = $this->pool->register($name);
        $this->logDebug('Final transaction name ' . $name);
        $span->setAttribute(self::TRANSACTION_NAME_ATTRIBUTE, $name);
    }
}
