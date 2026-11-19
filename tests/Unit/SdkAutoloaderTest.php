<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use OpenTelemetry\SDK\Common\Configuration\Variables;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Variables as SolarwindsEnv;
use Solarwinds\ApmPhp\SdkAutoloader;

class SdkAutoloaderTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        // Reset environment and server variables before each test
        $_SERVER = [];
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_CONFIG_FILE . '=');
    }

    public function test_autoload_returns_false_when_disabled(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=false');
        $this->assertFalse(SdkAutoloader::autoload());
    }

    public function test_autoload_returns_false_when_excluded_url(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $_SERVER['REQUEST_URI'] = '/foo';
        $this->assertFalse(SdkAutoloader::autoload());
    }

    public function test_is_enabled_true_and_false(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        $this->assertTrue(SdkAutoloader::isEnabled());
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=false');
        $this->assertFalse(SdkAutoloader::isEnabled());
    }

    public function test_is_enabled_defaults_to_true_when_variable_is_unset(): void
    {
        // putenv without '=' removes the variable from the environment entirely
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED);
        unset($_SERVER[SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED], $_ENV[SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED]);
        $this->assertFalse(getenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED), 'Precondition: variable must be genuinely unset');
        $this->assertTrue(SdkAutoloader::isEnabled());
    }

    public function test_is_enabled_defaults_to_true_when_variable_is_empty(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=');
        $this->assertTrue(SdkAutoloader::isEnabled());
    }

    public function test_is_enabled_ignores_otel_php_autoload_enabled(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED);
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=false');

        try {
            $this->assertTrue(SdkAutoloader::isEnabled());
        } finally {
            putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED);
        }
    }

    public function test_is_excluded_url_with_no_config(): void
    {
        $this->assertFalse(SdkAutoloader::isExcludedUrl());
    }

    public function test_is_excluded_url_with_no_request_uri(): void
    {
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $this->assertFalse(SdkAutoloader::isExcludedUrl());
    }

    public function test_is_excluded_url_with_match(): void
    {
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $_SERVER['REQUEST_URI'] = '/foo';
        $this->assertTrue(SdkAutoloader::isExcludedUrl());
    }

    public function test_is_excluded_url_with_no_match(): void
    {
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=bar');
        $_SERVER['REQUEST_URI'] = '/foo';
        $this->assertFalse(SdkAutoloader::isExcludedUrl());
    }

    public function test_autoload_no_throws_if_config_file_set_and_class_missing(): void
    {
        putenv(SolarwindsEnv::SW_APM_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_CONFIG_FILE . '=foo.yaml');
        // Simulate SdkConfiguration class missing
        if (class_exists('OpenTelemetry\\Config\\SDK\\Configuration', false)) {
            $this->markTestSkipped('Cannot test missing SdkConfiguration class if it is loaded.');
        } else {
            SdkAutoloader::autoload();
            $this->assertTrue(true); // If no exception is thrown, the test passes
        }
    }

    // Additional tests for environment and file-based initializers, and instrumentation registration,
    // would require extensive mocking of OpenTelemetry internals and are best covered with integration tests.
}
