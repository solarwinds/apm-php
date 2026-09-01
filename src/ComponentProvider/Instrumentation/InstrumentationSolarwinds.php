<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Instrumentation;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\InstrumentationConfiguration;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Solarwinds\ApmPhp\ComponentProvider\Validation\Validation as SwoValidation;
use Solarwinds\ApmPhp\Propagator\SwoTraceState\SwoTraceStatePropagator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<InstrumentationConfiguration>
 */

final class InstrumentationSolarwinds implements ComponentProvider
{
    /**
     * @param array{} $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): InstrumentationConfiguration
    {
        return new SolarwindsConfig($properties);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('solarwinds');
        $node
            ->children()
            ->scalarNode('fuzz')->isRequired()->cannotBeEmpty()->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;

        return $node;
    }
}
