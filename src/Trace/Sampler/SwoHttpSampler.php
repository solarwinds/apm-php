<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * SwoHttpSampler is a sampler that uses the HttpSampler for sampling decisions, wrapped in a ParentBased sampler to respect parent context's sampling decision.
 */
class SwoHttpSampler implements SamplerInterface
{
    private readonly ParentBased $parentBased;
    private static ?self $instance = null;
    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null, ?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null, CacheExtensionInterface $cacheExtension = new CacheExtension())
    {
        $http = new HttpSampler($meterProvider, $config, $initial, $client, $requestFactory, $cacheExtension);
        $this->parentBased = new ParentBased($http, $http, $http);
    }

    public static function getInstance(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null, ?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null, CacheExtensionInterface $cacheExtension = new CacheExtension()): self
    {
        if (null === self::$instance) {
            self::$instance = new self($meterProvider, $config, $initial, $client, $requestFactory, $cacheExtension);
        }

        return self::$instance;
    }

    #[\Override]
    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        return $this->parentBased->shouldSample($parentContext, $traceId, $spanName, $spanKind, $attributes, $links);
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->parentBased->getDescription();
    }
}
