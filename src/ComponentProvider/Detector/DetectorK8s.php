<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Detector;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
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
     * @return ResourceDetectorInterface
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): ResourceDetectorInterface
    {
        return new K8s(pod_name: $properties['pod_name'] ?? null, pod_uid: $properties['pod_uid'] ?? null, namespace: $properties['namespace'] ?? null, namespaceFile: null, mountInfoFile: null);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('k8s');
        $node
            ->children()
                ->scalarNode('namespace')->validate()->always(Validation::ensureString())->end()->end()
                ->scalarNode('pod_uid')->validate()->always(Validation::ensureString())->end()->end()
                ->scalarNode('pod_name')->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;

        return $node;
    }
}
