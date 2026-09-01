<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Distribution;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\SDK\Common\Distribution\DistributionConfiguration;
use Override;
use Solarwinds\ApmPhp\Common\Distribution\ApmPhpDistribution;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<DistributionConfiguration>
 */
final class DistributionSolarwinds implements ComponentProvider
{
    #[Override]
    public function createPlugin(array $properties, Context $context): DistributionConfiguration
    {
        return new ApmPhpDistribution(collector: $properties['collector'] ?? 'nothing');
    }

    #[Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('solarwinds');
        $node
            ->children()
            ->scalarNode('collector')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ;

        return $node;
    }
}