<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Integration;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Configuration\Variables;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\SdkAutoloader;

class SdkAutoloaderIntegrationTest extends TestCase
{
    private string $tempConfigFile;

    #[\Override]
    protected function setUp(): void
    {
        $_SERVER = [];
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_CONFIG_FILE . '=');
        $this->tempConfigFile = sys_get_temp_dir() . '/otel_test_config.yaml';
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_CONFIG_FILE . '=');
    }

    public function test_autoload_with_environment_initialization(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        $result = SdkAutoloader::autoload();
        $this->assertTrue($result, 'Autoload should return true when enabled and not excluded.');
        // Optionally, check for side effects or global state changes here
    }

    public function test_autoload_with_config_file_initialization(): void
    {
        // Create a minimal valid YAML config file for OpenTelemetry
        file_put_contents($this->tempConfigFile, 'instrumentation:');
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_CONFIG_FILE . '=' . $this->tempConfigFile);
        $result = SdkAutoloader::autoload();
        $this->assertTrue($result, 'Autoload should return true when config file is present and valid.');
        // Optionally, check for side effects or global state changes here
    }

    public function test_autoload_with_excluded_url(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $_SERVER['REQUEST_URI'] = '/foo';
        $result = SdkAutoloader::autoload();
        $this->assertFalse($result, 'Autoload should return false for excluded URL.');
    }

    public function test_autoload_disabled_does_not_end_application_span(): void
    {
        $exporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($exporter))
            ->build();
        $span = $tracerProvider->getTracer('test')->spanBuilder('application')->startSpan();
        $scope = $span->activate();
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=false');

        try {
            $this->assertFalse(SdkAutoloader::autoload());
            $this->assertSame($span->getContext()->getSpanId(), Span::fromContext(Context::getCurrent())->getContext()->getSpanId());
            $this->assertCount(0, $exporter->getSpans());
        } finally {
            $scope->detach();
            $span->end();
        }

        $this->assertCount(1, $exporter->getSpans());
    }
}
