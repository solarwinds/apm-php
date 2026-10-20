<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;

/**
 * SwoJsonSampler is a sampler that uses the JsonSampler for sampling decisions, wrapped in a ParentBased sampler to respect parent context's sampling decision.
 */
class SwoJsonSampler implements SamplerInterface
{
    private readonly ParentBased $parentBased;
    private static ?self $instance = null;
    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, string $path = KnownValues::VALUE_SAMPLER_SWO_JSON_DEFAULT_PATH)
    {
        $json = new JsonSampler($meterProvider, $config, $path);
        $this->parentBased = new ParentBased($json, $json, $json);
    }

    public static function getInstance(?MeterProviderInterface $meterProvider, Configuration $config, string $path = KnownValues::VALUE_SAMPLER_SWO_JSON_DEFAULT_PATH): self
    {
        if (null === self::$instance) {
            self::$instance = new self($meterProvider, $config, $path);
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
