<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class TriggerTraceUtil
{
    public static function validateSignature(string $header, string $signature, ?string $key, ?int $timestamp): Auth
    {
        if ($key === null) {
            return Auth::NO_SIGNATURE_KEY;
        }

        $now = time();
        if ($timestamp === null || abs($now - $timestamp) > 5 * 60) {
            return Auth::BAD_TIMESTAMP;
        }

        $digest = hash_hmac('sha1', $header, $key);
        return hash_equals($signature, $digest) ? Auth::OK : Auth::BAD_SIGNATURE;
    }
}
