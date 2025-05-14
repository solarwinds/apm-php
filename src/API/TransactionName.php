<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\API;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Trace\LocalRootSpan;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNameSpanProcessor;

final class TransactionName
{
    use LogsMessagesTrait;

    /**
     * Retrieve the local root span (root-most active span which has a remote or invalid parent) and try to set transaction name in it
     */
    public static function set(string $name): bool
    {
        if (empty($name)) {
            self::logDebug('Transaction name cannot be empty');

            return false;
        }
        $span = LocalRootSpan::current();
        if ($span instanceof ReadableSpanInterface) {
            $spanProcessor = TransactionNameSpanProcessor::getInstance();
            if ($spanProcessor instanceof TransactionNameSpanProcessor) {
                $name = $spanProcessor->getTransactionNamePool()->register($name);
                $span->setAttribute(TransactionNameSpanProcessor::TRANSACTION_NAME_ATTRIBUTE, $name);

                return true;
            }
        }
        self::logDebug('Unable to get local root span or transaction name span processor');

        return false;
    }
}
