<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

use Exception;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

/**
 * Phan seems to struggle with the variadic arguments in the latest version
 * @phan-file-suppress PhanParamTooFewUnpack
 */

class JsonSampler extends Sampler
{
    use LogsMessagesTrait;

    private string $path;

    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, string $path = '/tmp/solarwinds-apm-settings.json')
    {
        parent::__construct($meterProvider, $config);
        $this->path = $path;
    }

    private function request(): void
    {
        try {
            if (!file_exists($this->path)) {
                $this->logError('Settings file does not exist ' . $this->path, ['path' => $this->path]);

                return;
            }
            $content = file_get_contents($this->path);
            if ($content === false) {
                $this->logError('Unable to get content from ' . $this->path, ['path' => $this->path]);

                return;
            }
            $unparsed = json_decode($content, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
            if (!is_array($unparsed) || count($unparsed) !== 1) {
                $this->logError('Invalid settings file', ['data' => $unparsed]);

                return;
            }
            if (array_key_exists(0, $unparsed)) {
                $this->parsedAndUpdateSettings($unparsed[0]);
            }
        } catch (\JsonException $ex) {
            $this->logError('json_decode error', ['path' => $this->path, 'error' => $ex->getMessage()]);
        } catch (Exception $e) {
            $this->logError('JsonSampler exception', ['path' => $this->path, 'error' => $e->getMessage()]);
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
        return sprintf('JSON Sampler (%s)', $this->path);
    }
}
