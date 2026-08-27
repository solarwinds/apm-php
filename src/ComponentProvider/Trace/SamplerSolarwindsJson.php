<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SamplerInterface>
 */
final class SamplerSolarwindsJson implements ComponentProvider
{
    /**
     * @param array{} $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SamplerInterface
    {
        $config = new Configuration(service:"name", collector:"name", token:"name", tracingMode: false, triggerTraceEnabled: false, transactionSettings: []);
        $path = $properties['path'] ?? '/tmp/solarwinds-apm-settings.json';
        return new JsonSampler($context->meterProvider, $config, $path);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('solarwinds_json');
        $node
            ->children()
            ->scalarNode('path')->defaultValue('/tmp/solarwinds-apm-settings.json')->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;
        return $node;
    }
}
