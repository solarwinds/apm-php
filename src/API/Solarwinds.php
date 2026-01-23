<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\API;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Globals;
use Solarwinds\ApmPhp\Trace\Sampler\ParentBasedSampler;

final class Solarwinds
{
    use LogsMessagesTrait;

    public static function waitUntilReady(int $timeoutMs = 3000) : bool
    {
        $tracerProviderInterface = Globals::tracerProvider();
        if ($tracerProviderInterface instanceof \OpenTelemetry\SDK\Trace\TracerProvider) {
            $tracerProvider = $tracerProviderInterface;
            $samplerInterface = $tracerProvider->getSampler();
            if ($samplerInterface instanceof ParentBasedSampler) {
                $sampler = $samplerInterface;

                return $sampler->waitUntilReady($timeoutMs);
            }
            self::logDebug('Solarwinds not ready. SamplerInterface is not an instance of ExtensionSampler');

            return false;

        }
        self::logDebug('Solarwinds not ready. TracerProviderInterface is not an instance of TracerProvider');

        return false;
    }
}
