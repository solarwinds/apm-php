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
    const PATH = '/tmp/solarwinds-apm-settings.json';

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, ?Settings $initial = null)
    {
        parent::__construct($meterProvider, $config, $initial);

        self::logInfo('Starting HTTP hardcode sampler');
    }

    private function request(): void
    {
        $content = file_get_contents(HttpSampler::PATH);
        if ($content === false) {
            $this->logError('Unable to get content from ' . HttpSampler::PATH, ['path' => HttpSampler::PATH]);

            return;
        }
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
