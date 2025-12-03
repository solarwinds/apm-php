<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use Exception;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 */

class HttpAsyncSampler extends Sampler
{
    use LogsMessagesTrait;

    private string $url;
    private array $headers;
    private string $service;
    private string $hostname;
    private ?string $lastWarningMessage = null;
    private ?int $request_timestamp = null;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null, private readonly Browser $browser = new Browser())
    {
        parent::__construct($meterProvider, $config, $initial);

        $this->url = $config->getCollector();
        $this->service = urlencode($config->getService());
        $this->headers = $config->getHeaders();
        $this->hostname = urlencode(gethostname());

        $this->loop();
        self::logInfo('Starting HTTP async sampler loop');
    }

    private function loop(): void
    {
        if ($this->request_timestamp !== null && $this->request_timestamp + 60 >= time()) {
            return;
        }

        try {
            $url = $this->url . '/v1/settings/' . $this->service . '/' . $this->hostname;
            $this->logDebug('Retrieving sampling settings from ' . $url);
            $this->browser->get($url, $this->headers)->then(function (ResponseInterface $response) use ($url) {
                $contentType = $response->getHeaderLine('Content-Type');
                if (stripos($contentType, 'application/json') === false) {
                    $this->warn('Received unexpected content type ' . $contentType . ' from ' . $url);

                    return;
                }
                $content = $response->getBody()->getContents();
                $this->logDebug(microtime(true) . ' Received sampling settings response ' . $content);
                $unparsed = json_decode($content, true);
                $parsed = $this->parsedAndUpdateSettings($unparsed);
                if (!$parsed) {
                    $this->warn('Retrieved sampling settings are invalid');

                    return;
                }
                $this->lastWarningMessage = null;
            }, null);
            $this->logInfo(microtime(true) . ' sent request to ' . $url);
            $this->request_timestamp = time();
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
