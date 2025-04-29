<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use Exception;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 */

class HttpSampler extends Sampler
{
    use LogsMessagesTrait;

    private string $url;
    private array $headers;
    private string $service;
    private string $hostname;
    private ?string $lastWarningMessage = null;

    private ?int $request_timestamp = null;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        $this->url = $config->getCollector();
        $this->service = urlencode($config->getService());
        $this->headers = $config->getHeaders();
        $this->hostname = urlencode(gethostname());

        $this->loop();
    }

    private function loop(): void
    {
        if ($this->request_timestamp !== null && $this->request_timestamp + 60 >= time()) {
            return;
        }

        try {
            $url = $this->url . '/v1/settings/' . $this->service . '/' . $this->hostname;
            $this->logDebug('Retrieving sampling settings from ' . $url);
            $ctx = stream_context_create(
                [
                    'http' => [
                        'header' => $this->headers,
                        'method' => 'GET',
                    ],
                ]
            );
            $this->request_timestamp = time();
            $response = file_get_contents($url, false, $ctx);
            if ($response === false) {
                $this->warn('Unable to get content from ' . $url);

                return;
            }
            $this->logDebug('Received sampling settings response ' . $response);
            $unparsed = json_decode($response, true);
            $parsed = $this->parsedAndUpdateSettings($unparsed);
            if (!$parsed) {
                $this->warn('Retrieved sampling settings are invalid');

                return;
            }
            $this->lastWarningMessage = null;
        } catch (Exception $e) {
            $this->warn('Unexpected error occurred: ' . $e->getMessage());
        }
    }

    private function warn(string $message, array $context = []): void
    {
        if ($message !== $this->lastWarningMessage) {
            $this->logWarning($message, $context);
            $this->lastWarningMessage = $message;
        } else {
            $this->logDebug($message, $context);
        }
    }

    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        $this->loop();

        return parent::shouldSample(...func_get_args());
    }

    public function getDescription(): string
    {
        return sprintf('HTTP Sampler (%s)', parse_url($this->url, PHP_URL_HOST));
    }
}
