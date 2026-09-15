<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Trace;

use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNameSpanProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanProcessorInterface>
 */
final class SpanProcessorTransactionName implements ComponentProvider
{
    /**
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SpanProcessorInterface
    {
        return TransactionNameSpanProcessor::getInstance($properties['name']);
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('transaction_name');
        $node
            ->children()
            ->scalarNode('name')->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;
        return $node;
    }
}
