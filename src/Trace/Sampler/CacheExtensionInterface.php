<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

interface CacheExtensionInterface
{
    public function isExtensionLoaded(): bool;

    public function getCache(string $collector, string $token, string $serviceName): string|false;

    public function putCache(string $collector, string $token, string $serviceName, string $settings): bool;

    public function getBucketState(string $pid): string|false;

    public function putBucketState(string $pid, string $bucketState): bool;
}
