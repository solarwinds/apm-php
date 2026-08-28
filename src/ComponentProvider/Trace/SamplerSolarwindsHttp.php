<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\ComponentProvider\Validation\Validation as SwoValidation;
use Solarwinds\ApmPhp\Trace\Sampler\HttpSampler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SamplerInterface>
 */
final class SamplerSolarwindsHttp implements ComponentProvider
{
    /**
     * @param array{} $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SamplerInterface
    {
        $config = new Configuration(service:'unknown_service', collector:$properties['collector'], token:explode(':', $properties['service_key'], 2)[0], tracingMode: $properties['tracing_mode'], triggerTraceEnabled: $properties['trigger_tracing_enabled'], transactionSettings: $properties['transaction_settings']);
        return new HttpSampler($context->meterProvider, $config);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('solarwinds_http');
        $node
            ->children()
                ->scalarNode('service_key')->isRequired()->cannotBeEmpty()->validate()->always(SwoValidation::ensureServiceKey())->end()->end()
                ->scalarNode('collector')->defaultValue("apm.collector.na-01.cloud.solarwinds.com")->validate()->always(Validation::ensureString())->end()->end()
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
            ->end()
        ;
        return $node;
    }
}
