<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\ResponsePropagator;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use Solarwinds\ApmPhp\ResponsePropagator\XTrace\XTraceResponsePropagator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResponsePropagatorInterface>
 */
final class ResponsePropagatorXTrace implements ComponentProvider
{
    /**
     * @param array{} $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): ResponsePropagatorInterface
    {
        return XTraceResponsePropagator::getInstance();
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        return $builder->arrayNode('xtrace');
    }
}
