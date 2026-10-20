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
use Solarwinds\ApmPhp\ComponentProvider\Validation\Validation as SwoValidation;
use Solarwinds\ApmPhp\Trace\Sampler\SwoJsonSampler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SamplerInterface>
 */
final class SamplerSwoJson implements ComponentProvider
{
    /**
     * @phan-suppress PhanParamSignatureMismatch
     * @param array{} $properties
     * @param Context $context
     * @return SamplerInterface
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SamplerInterface
    {
        $config = new Configuration(service:'', collector:'', token:'', tracingMode: $properties['tracing_mode'] ?? null, triggerTraceEnabled: $properties['trigger_tracing_enabled'] ?? true, transactionSettings: $properties['transaction_settings'] ?? []);

        return new SwoJsonSampler($context->meterProvider, $config, $properties['path'] ?? KnownValues::VALUE_SAMPLER_SWO_JSON_DEFAULT_PATH);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('swo_json');
        $node
            ->children()
                ->scalarNode('path')->defaultValue(KnownValues::VALUE_SAMPLER_SWO_JSON_DEFAULT_PATH)->validate()->always(Validation::ensureString())->end()->end()
                ->booleanNode('tracing_mode')->defaultTrue()->end()
                ->booleanNode('trigger_tracing_enabled')->defaultTrue()->end()
                ->arrayNode('transaction_settings')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('tracing')->isRequired()->cannotBeEmpty()->validate()->always(SwoValidation::ensureEnabledDisabled())->end()->end()
                            ->scalarNode('regex')->isRequired()->cannotBeEmpty()->validate()->always(Validation::ensureRegexPattern())->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
