<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Detector;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use Solarwinds\ApmPhp\Resource\Detectors\K8s;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResourceDetectorInterface>
 */
final class DetectorK8s implements ComponentProvider
{
    /**
     * @param array{} $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): ResourceDetectorInterface
    {
        return new K8s();
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('k8s');
        $node
            ->children()
                ->scalarNode('pod_namespace')->end()
                ->scalarNode('pod_uid')->end()
                ->scalarNode('pod_name')->end()
            ->end()
        ;
        return $node;
    }
}