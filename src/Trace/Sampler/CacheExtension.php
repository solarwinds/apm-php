<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;

/**
 * @phan-file-suppress PhanUndeclaredFunction
 */
class CacheExtension implements CacheExtensionInterface
{
    use LogsMessagesTrait;

    #[\Override]
    public function isExtensionLoaded(): bool
    {
        if (!extension_loaded('apm_ext')) {
            $this->logDebug('apm_ext extension is not loaded');

            return false;
        }

        return true;
    }
    #[\Override]
    public function getCache(string $collector, string $token, string $serviceName): string|false
    {
        if (function_exists('\Solarwinds\Cache\get')) {
            return \Solarwinds\Cache\get($collector, $token, $serviceName);
        }
        $this->logWarning('\Solarwinds\Cache\get function from apm_ext does not exist');

        return false;
    }

    #[\Override]
    public function putCache(string $collector, string $token, string $serviceName, string $settings): bool
    {
        if (function_exists('\Solarwinds\Cache\put')) {
            return \Solarwinds\Cache\put($collector, $token, $serviceName, $settings);
        }
        $this->logWarning('\Solarwinds\Cache\put function from apm_ext does not exist');

        return false;
    }

    #[\Override]
    public function getBucketState(string $pid): string|false
    {
        if (function_exists('\Solarwinds\Cache\getBucketState')) {
            return \Solarwinds\Cache\getBucketState($pid);
        }
        $this->logWarning('\Solarwinds\Cache\getBucketState function from apm_ext does not exist');

        return false;
    }

    #[\Override]
    public function putBucketState(string $pid, string $bucketState): bool
    {
        if (function_exists('\Solarwinds\Cache\putBucketState')) {
            return \Solarwinds\Cache\putBucketState($pid, $bucketState);
        }
        $this->logWarning('\Solarwinds\Cache\putBucketState function from apm_ext does not exist');

        return false;
    }
}
