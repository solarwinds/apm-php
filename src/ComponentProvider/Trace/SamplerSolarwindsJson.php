<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;
use Solarwinds\ApmPhp\ComponentProvider\Validation\Validation as SwoValidation;
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
        $config = new Configuration(service:"", collector:"", token:"", tracingMode: false, triggerTraceEnabled: false, transactionSettings: []);

        return new JsonSampler($context->meterProvider, $config, $properties['path']);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('solarwinds_json');
        $node
            ->children()
            ->booleanNode('tracing_mode')->defaultTrue()->end()
            ->booleanNode('trigger_tracing_enabled')->defaultTrue()->end()
            ->arrayNode('transaction_settings')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('tracing')->isRequired()->cannotBeEmpty()->validate()->always(SwoValidation::ensureEnabledDisabled())->end()->end()
                        ->scalarNode('regex')->defaultNull()->validate()->always(Validation::ensureRegexPattern())->end()->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('path')->defaultValue(KnownValues::VALUE_SAMPLER_SOLARWINDS_JSON_DEFAULT_PATH)->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;
        return $node;
    }
}
