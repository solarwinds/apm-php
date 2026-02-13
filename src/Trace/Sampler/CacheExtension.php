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

    public function isExtensionLoaded(): bool
    {
        if (!extension_loaded('apm_ext')) {
            $this->logDebug('apm_ext extension is not loaded');

            return false;
        }

        return true;
    }

    public function getCache(string $collector, string $token, string $serviceName): string|false
    {
        if (function_exists('\Solarwinds\Cache\get')) {
            return \Solarwinds\Cache\get($collector, $token, $serviceName);
        }
        $this->logWarning('\Solarwinds\Cache\get function from apm_ext does not exist');

        return false;
    }

    public function putCache(string $collector, string $token, string $serviceName, string $settings): bool
    {
        if (function_exists('\Solarwinds\Cache\put')) {
            return \Solarwinds\Cache\put($collector, $token, $serviceName, $settings);
        }
        $this->logWarning('\Solarwinds\Cache\put function from apm_ext does not exist');

        return false;
    }
}
