<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 */

class HttpSampler extends Sampler
{
    use LogsMessagesTrait;

    private ?string $lastWarningMessage = null;
    const SETTING = '{"value":1000000,"flags":"SAMPLE_START,SAMPLE_THROUGH_ALWAYS,SAMPLE_BUCKET_ENABLED,TRIGGER_TRACE","timestamp":1768001828,"ttl":120,"arguments":{"BucketCapacity":2,"BucketRate":1,"TriggerRelaxedBucketCapacity":20,"TriggerRelaxedBucketRate":1,"TriggerStrictBucketCapacity":6,"TriggerStrictBucketRate":0.1,"SignatureKey":"a9012f2c6b25d1f5d8b87ed1a3858abd230cac7c99e8ec2aeacfaba6aa123456"}}';

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        self::logInfo('Starting HTTP hardcode sampler');
    }

    private function request(): void
    {
        $content = HttpSampler::SETTING;
        $this->logDebug('Received sampling settings response ' . $content);
        $unparsed = json_decode($content, true);
        $unparsed['timestamp'] = time();
        $parsed = $this->parsedAndUpdateSettings($unparsed);
        if (!$parsed) {
            $this->warn('Retrieved sampling settings are invalid');
        }
    }

    private function warn(string $message, array $context = []): void
    {
        if ($message !== $this->lastWarningMessage) {
            $this->logWarning($message, $context);
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
        return 'HTTP hardcode Sampler';
    }
}
