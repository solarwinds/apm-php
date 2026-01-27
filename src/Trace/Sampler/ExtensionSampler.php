<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack, PhanUndeclaredFunction
 */

class ExtensionSampler extends Sampler
{
    use LogsMessagesTrait;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config)
    {
        parent::__construct($meterProvider, $config);
    }

    public function isExtensionLoaded(): bool
    {
        return extension_loaded('apm_ext');
    }

    public function settingsFunction(int $timeoutMs = -1): string
    {
        if (function_exists('\Solarwinds\Sampler\settings')) {
            if ($timeoutMs <= 0) {
                return \Solarwinds\Sampler\settings();
            }
            $settings = '';
            while ($timeoutMs > 0 && strlen($settings = \Solarwinds\Sampler\settings()) == 0) {
                usleep(1000); // 1ms sleep
                $timeoutMs--;
            }

            return $settings;
        }
        $this->logWarning('settings function from apm_ext does not exist');

        return '';
    }

    private function request(): void
    {
        if ($this->isExtensionLoaded()) {
            $settings = $this->settingsFunction();
            $this->logInfo('Retrieved sampling settings from apm_ext extension: ' . $settings);
            if (strlen($settings) > 0) {
                try {
                    $unparsed = json_decode($settings, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                    $parsed = $this->parsedAndUpdateSettings($unparsed);
                    if (!$parsed) {
                        $this->logError('Retrieved sampling settings are invalid');

                        return;
                    }
                } catch (\JsonException $ex) {
                    $this->logError('json_decode error', ['error' => $ex->getMessage()]);
                }
            }
        } else {
            $this->logInfo('apm_ext extension not found');
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
        return 'Extension Sampler (apm_ext)';
    }
}
