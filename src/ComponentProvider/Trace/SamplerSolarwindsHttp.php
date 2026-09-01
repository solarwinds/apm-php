<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\SDK\Common\Distribution\DistributionRegistry;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Common\Configuration\KnownValues;
use Solarwinds\ApmPhp\Common\Distribution\ApmPhpDistribution;
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
        // Unable to do that as the properties here only contains the child of solarwinds_http
//        $distributionProperties = new DistributionRegistry();
//        foreach ($properties['distribution'] as $distributionConfiguration) {
//            $distributionProperties->add($distributionConfiguration->create($context));
//        }
//
//        $distributionConfiguration = $distributionProperties->getDistributionConfiguration(ApmPhpDistribution::class) ?? new ApmPhpDistribution();

        $service_key = $properties['service_key'];
        $arr = explode(':', $service_key, 2);
        $token = $arr[0];
        $service_name = $arr[1];
        $list = $context->getExtension(ResourceInfo::class);
        $service_name_from_resource = $list->getAttributes()->get('service.name');
        $config = new Configuration(service:$service_name_from_resource??$service_name??"unknown_service:php", collector:$properties['collector'], token:$token, tracingMode: $properties['tracing_mode'], triggerTraceEnabled: $properties['trigger_tracing_enabled'], transactionSettings: $properties['transaction_settings']);
        return new HttpSampler($context->meterProvider, $config);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
//        $n = $builder->arrayNode('distribution');
//        $n->children()
//            ->scalarNode('collector')->isRequired()->cannotBeEmpty()->validate()->always(Validation::ensureString())->end()->end()
//            ->end()
//            ;
//        return $n;
        $node = $builder->arrayNode('solarwinds_http');
        $node
            ->children()
                ->scalarNode('service_key')->isRequired()->cannotBeEmpty()->validate()->always(SwoValidation::ensureServiceKey())->end()->end()
                ->scalarNode('collector')->defaultValue(KnownValues::VALUE_SAMPLER_SOLARWINDS_HTTP_DEFAULT_APM_COLLECTOR)->validate()->always(Validation::ensureString())->end()->end()
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
