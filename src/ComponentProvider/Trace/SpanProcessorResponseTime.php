<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use Solarwinds\ApmPhp\Trace\SpanProcessor\ResponseTimeSpanProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanProcessorInterface>
 */
final class SpanProcessorResponseTime implements ComponentProvider
{
    /**
     * @phan-suppress PhanParamSignatureMismatch
     * @param array{} $properties
     * @param Context $context
     * @return SpanProcessorInterface
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SpanProcessorInterface
    {
        return new ResponseTimeSpanProcessor($context->meterProvider);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        return $builder->arrayNode('response_time');
    }
}
