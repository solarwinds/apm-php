<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Common\Http\Psr\Client\Discovery;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack, PhanUndeclaredFunction
 */

class HttpSampler extends Sampler
{
    use LogsMessagesTrait;

    private string $url;
    private array $headers;
    private string $service;
    private string $token;
    private string $hostname;
    private ?string $lastWarningMessage = null;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null, ?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        $this->url = $config->getCollector();
        $this->service = urlencode($config->getService());
        $this->token = $config->getToken();
        $this->headers = $config->getHeaders();
        $this->hostname = urlencode(gethostname());
        $this->client = $client ?? Discovery::find([
            'timeout' => 10,
        ]);
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();

        self::logInfo('Starting HTTP sampler');
    }

    public function isExtensionLoaded(): bool
    {
        if (!extension_loaded('apm_ext')) {
            $this->logDebug('apm_ext extension is not loaded');

            return false;
        }

        return true;
    }

    public function getCache(string $collector, string $token, string $serviceName): string|false
    {
        if (function_exists('\Solarwinds\Cache\get')) {
            return \Solarwinds\Cache\get($collector, $token, $serviceName);
        }
        $this->logWarning('\Solarwinds\Cache\get function from apm_ext does not exist');

        return false;
    }

    public function putCache(string $collector, string $token, string $serviceName, string $settings): bool
    {
        if (function_exists('\Solarwinds\Cache\put')) {
            return \Solarwinds\Cache\put($collector, $token, $serviceName, $settings);
        }
        $this->logWarning('\Solarwinds\Cache\put function from apm_ext does not exist');

        return false;
    }

    private function request(): void
    {
        try {
            // Try from cache
            if ($this->isExtensionLoaded()) {
                $cached = $this->getCache($this->url, $this->token, $this->service);
                $this->logDebug('cached = ' . $cached);
                // $cached = "{\"value\":1000000,\"flags\":\"SAMPLE_START,SAMPLE_THROUGH_ALWAYS,SAMPLE_BUCKET_ENABLED,TRIGGER_TRACE\",\"timestamp\":1770065186,\"ttl\":120,\"arguments\":{\"BucketCapacity\":2,\"BucketRate\":1,\"TriggerRelaxedBucketCapacity\":20,\"TriggerRelaxedBucketRate\":1,\"TriggerStrictBucketCapacity\":6,\"TriggerStrictBucketRate\":0.1,\"SignatureKey\":\"a9012f2c6b25d1f5d8b87ed1a3858abd230cac7c99e8ec2aeacfaba6aa31ffc0\"}}";
                if ($cached !== false) {
                    $unparsed = json_decode($cached, true);
                    $this->logDebug('unparsed' . $unparsed);
//                    if (
//                        isset($unparsed['value'], $unparsed['timestamp'], $unparsed['ttl']) &&
//                        is_numeric($unparsed['value']) &&
//                        is_numeric($unparsed['timestamp']) &&
//                        is_numeric($unparsed['ttl'])
//                    ) {
                    if (is_array($unparsed) && (int) $unparsed['timestamp'] + (int) $unparsed['ttl'] <= time()) {
                        $parsed = $this->parsedAndUpdateSettings($unparsed);
                        if ($parsed) {
                            // return if settings are valid
                            $this->logDebug('Used sampling settings from cache: ' . $cached);

                            return;
                        } else {
                            $this->logDebug('Failed to parse sampling settings from cache: ' . $cached);
                        }
                    } else {
                        $this->logDebug('Unable to parse JSON data from cache: ' . $cached);
                    }
                } else {
                    $this->logDebug('Failed to read settings from cache');
                }
            }
            $url = $this->url . '/v1/settings/' . $this->service . '/' . $this->hostname;
            $this->logDebug('Retrieving sampling settings from ' . $url);
            $req = $this->requestFactory->createRequest('GET', $url);
            // Authorization header
            $req = $req->withHeader('Authorization', 'Bearer ' . $this->token);
            // Other headers
            foreach ($this->headers as $key => $value) {
                $req = $req->withHeader($key, $value);
            }
            $res = $this->client->sendRequest($req);
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
            $parsed = $this->parsedAndUpdateSettings($unparsed);
            if (!$parsed) {
                $this->warn('Retrieved sampling settings are invalid');

                return;
            }
            $this->lastWarningMessage = null;
            // Write cache
            if ($this->isExtensionLoaded()) {
                if (!$this->putCache($this->url, $this->token, $this->service, $content)) {
                    $this->warn('Failed to cache sampling settings');
                } else {
                    $this->logDebug('Write sampling settings to cache');
                }
            }
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
        $this->request();

        return parent::shouldSample(...func_get_args());
    }

    public function getDescription(): string
    {
        return sprintf('HTTP Sampler (%s)', parse_url($this->url, PHP_URL_HOST));
    }
}
