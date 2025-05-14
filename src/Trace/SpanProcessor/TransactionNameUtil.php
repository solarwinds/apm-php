<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;

class TransactionNameUtil
{
    use LogsMessagesTrait;
    public static function resolveTransactionName(string $uri): string
    {
        $parsedUri = parse_url($uri);
        if ($parsedUri === false) {
            return 'unknown';
        }
        if (isset($parsedUri['path']) && $parsedUri['path'] !== '') {
            $path = trim($parsedUri['path'], '/');
            $segments = explode('/', $path);
            $maxSupportedSegments = min(2, count($segments));
            $beforeJoin = implode('/', array_slice($segments, 0, $maxSupportedSegments));
            $ans = '/' . $beforeJoin;
        } else {
            $ans = '/';
        }

        return $ans;
    }
}
