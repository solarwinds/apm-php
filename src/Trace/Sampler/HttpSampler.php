<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

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
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null, ?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        $this->url = $config->getCollector();
        $this->service = urlencode($config->getService());
        $this->headers = $config->getHeaders();
        $this->hostname = urlencode(gethostname());
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();

        $this->loop();
        self::logInfo('Starting HTTP sampler loop');
    }

    private function loop(): void
    {
        if ($this->request_timestamp !== null && $this->request_timestamp + 60 >= time()) {
            return;
        }

        try {
            $url = "http://localhost:8080/";// $this->url . '/v1/settings/' . $this->service . '/' . $this->hostname;
            $this->logDebug('Retrieving sampling settings from ' . $url);
            $req = $this->requestFactory->createRequest('GET', $url);
            foreach ($this->headers as $key => $value) {
                $req = $req->withHeader($key, $value);
            }
            $res = $this->client->sendRequest($req);
            $this->request_timestamp = time();
            if ($res->getStatusCode() !== 200) {
                $this->warn('Received unexpected status code ' . $res->getStatusCode() . ' from ' . $url);

                return;
            }
            // Check if the content type is JSON
            $contentType = $res->getHeaderLine('Content-Type');
            if (stripos($contentType, 'application/json') === false) {
                $this->warn('Received unexpected content type ' . $contentType . ' from ' . $url);

                return;
            }
            $content = $res->getBody()->getContents();
            $this->logDebug('Received sampling settings response ' . $content);
            $unparsed = json_decode($content, true);
            $unparsed['timestamp'] = time();
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
