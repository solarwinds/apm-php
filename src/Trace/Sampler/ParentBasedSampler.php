<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use OpenTelemetry\SDK\Trace\Span;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 */

/**
 * Duplicated from ParentBased sampler in OpenTelemetry SDK to allow Solarwinds::ready() to work correctly.
 */
class ParentBasedSampler implements SamplerInterface
{
    public function __construct(private readonly SamplerInterface $root, private readonly SamplerInterface $remoteParentSampler = new AlwaysOnSampler(), private readonly SamplerInterface $remoteParentNotSampler = new AlwaysOffSampler(), private readonly SamplerInterface $localParentSampler = new AlwaysOnSampler(), private readonly SamplerInterface $localParentNotSampler = new AlwaysOffSampler())
    {
    }

    /**
     * Invokes the respective delegate sampler when parent is set or uses root sampler for the root span.
     * {@inheritdoc}
     */
    #[\Override]
    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        $parentSpan = Span::fromContext($parentContext);
        $parentSpanContext = $parentSpan->getContext();

        // Invalid parent SpanContext indicates root span is being created
        if (!$parentSpanContext->isValid()) {
            return $this->root->shouldSample(...func_get_args());
        }

        if ($parentSpanContext->isRemote()) {
            return $parentSpanContext->isSampled()
                ? $this->remoteParentSampler->shouldSample(...func_get_args())
                : $this->remoteParentNotSampler->shouldSample(...func_get_args());
        }

        return $parentSpanContext->isSampled()
            ? $this->localParentSampler->shouldSample(...func_get_args())
            : $this->localParentNotSampler->shouldSample(...func_get_args());
    }

    public function waitUntilReady(int $timeoutMs) : bool
    {
        //        if ($this->root instanceof Http) {
        //            $extensionSampler = $this->root;
        //
        //            return $extensionSampler->isExtensionLoaded() && strlen($extensionSampler->settingsFunction($timeoutMs)) > 0;
        //        }

        // For other sampler types, they are always ready as apm-php is single-threaded
        return true;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Solarwinds ParentBasedSampler+' . $this->root->getDescription();
    }
}
