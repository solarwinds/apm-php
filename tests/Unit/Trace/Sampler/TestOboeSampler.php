<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TraceState;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use Solarwinds\ApmPhp\Trace\Sampler\CacheExtension;
use Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface;
use Solarwinds\ApmPhp\Trace\Sampler\LocalSettings;
use Solarwinds\ApmPhp\Trace\Sampler\OboeSampler;
use Solarwinds\ApmPhp\Trace\Sampler\RequestHeaders;
use Solarwinds\ApmPhp\Trace\Sampler\ResponseHeaders;
use Solarwinds\ApmPhp\Trace\Sampler\Settings;

class TestOboeSampler extends OboeSampler
{
    private readonly LocalSettings $localSettings;
    private readonly RequestHeaders $requestHeaders;
    private ?ResponseHeaders $responseHeaders = null;

    public function __construct(?MeterProviderInterface $meterProvider, ?Settings $settings, LocalSettings $localSettings, RequestHeaders $requestHeaders, CacheExtensionInterface $cacheExtension = new CacheExtension())
    {
        parent::__construct($meterProvider, $cacheExtension);
        $this->localSettings = $localSettings;
        $this->requestHeaders = $requestHeaders;
        if ($settings instanceof Settings) {
            $this->updateSettings($settings);
        }
    }

    public function getResponseHeaders(): ?ResponseHeaders
    {
        return $this->responseHeaders;
    }

    #[\Override]
    public function setResponseHeaders(ResponseHeaders $headers, ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): ?TraceState
    {
        $this->responseHeaders = $headers;

        return null;
    }

    #[\Override]
    public function localSettings(ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): LocalSettings
    {
        return $this->localSettings;
    }

    #[\Override]
    public function requestHeaders(ContextInterface $parentContext, string $traceId, string $spanName, int $spanKind, AttributesInterface $attributes, array $links): RequestHeaders
    {
        return $this->requestHeaders;
    }

}
