<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Integration;

use OpenTelemetry\SDK\Common\Configuration\Variables;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\SdkAutoloader;

class SdkAutoloaderIntegrationTest extends TestCase
{
    private string $tempConfigFile;

    protected function setUp(): void
    {
        $_SERVER = [];
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_EXPERIMENTAL_CONFIG_FILE . '=');
        $this->tempConfigFile = sys_get_temp_dir() . '/otel_test_config.yaml';
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_EXPERIMENTAL_CONFIG_FILE . '=');
    }

    public function test_autoload_with_environment_initialization(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        $result = SdkAutoloader::autoload();
        $this->assertTrue($result, 'Autoload should return true when enabled and not excluded.');
        // Optionally, check for side effects or global state changes here
    }

    public function test_autoload_with_config_file_initialization(): void
    {
        // Create a minimal valid YAML config file for OpenTelemetry
        file_put_contents($this->tempConfigFile, 'instrumentation:');
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_EXPERIMENTAL_CONFIG_FILE . '=' . $this->tempConfigFile);
        $result = SdkAutoloader::autoload();
        $this->assertTrue($result, 'Autoload should return true when config file is present and valid.');
        // Optionally, check for side effects or global state changes here
    }

    public function test_autoload_with_excluded_url(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $_SERVER['REQUEST_URI'] = '/foo';
        $result = SdkAutoloader::autoload();
        $this->assertFalse($result, 'Autoload should return false for excluded URL.');
    }
}
