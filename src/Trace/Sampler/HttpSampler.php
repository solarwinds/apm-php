<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

// use Http\Discovery\Psr17FactoryDiscovery;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
// use OpenTelemetry\SDK\Common\Http\Psr\Client\Discovery;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
// use Psr\Http\Client\ClientInterface;
// use Psr\Http\Message\RequestFactoryInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 * @phan-file-suppress PhanUndeclaredFunction
 */

class HttpSampler extends Sampler
{
    use LogsMessagesTrait;

    private string $url;
    //    private array $headers;
    //    private string $service;
    //    private string $hostname;
    private ?string $lastWarningMessage = null;
    //    private ClientInterface $client;
    //    private RequestFactoryInterface $requestFactory;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        $this->url = $config->getCollector();
        //        $this->service = urlencode($config->getService());
        //        $this->headers = $config->getHeaders();
        //        $this->hostname = urlencode(gethostname());
        //        $this->client = $client ?? Discovery::find([
        //            'timeout' => 10,
        //        ]);
        //        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();

        self::logInfo('Starting HTTP sampler');
    }

    private function request(): void
    {
        if (extension_loaded('swo') && function_exists('\Solarwinds\Sampler\setting')) {
            $setting = \Solarwinds\Sampler\setting();
            $this->logInfo('Retrieved sampling settings from Swo extension: ' . $setting);
            if (strlen($setting) > 0) {
                $unparsed = json_decode($setting, true);
                $unparsed["values"] = 1000000;
                $unparsed["arguments"]["BucketCapacity"] = 2;
                $unparsed["arguments"]["BucketRate"] = 2;
                $parsed = $this->parsedAndUpdateSettings($unparsed);
                if (!$parsed) {
                    $this->warn('Retrieved sampling settings are invalid');

                    return;
                }
                $this->lastWarningMessage = null;
            }
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
