<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use OpenTelemetry\SDK\Common\Configuration\Variables;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\SdkAutoloader;

class SdkAutoloaderTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset environment and server variables before each test
        $_SERVER = [];
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=');
        putenv(Variables::OTEL_EXPERIMENTAL_CONFIG_FILE . '=');
    }

    public function test_autoload_returns_false_when_disabled(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=false');
        $this->assertFalse(SdkAutoloader::autoload());
    }

    public function test_autoload_returns_false_when_excluded_url(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_PHP_EXCLUDED_URLS . '=foo');
        $_SERVER['REQUEST_URI'] = '/foo';
        $this->assertFalse(SdkAutoloader::autoload());
    }

    public function test_is_enabled_true_and_false(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        $this->assertTrue(SdkAutoloader::isEnabled());
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=false');
        $this->assertFalse(SdkAutoloader::isEnabled());
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

    public function test_autoload_throws_if_config_file_set_and_class_missing(): void
    {
        putenv(Variables::OTEL_PHP_AUTOLOAD_ENABLED . '=true');
        putenv(Variables::OTEL_EXPERIMENTAL_CONFIG_FILE . '=foo.yaml');
        // Simulate SdkConfiguration class missing
        if (class_exists('OpenTelemetry\\Config\\SDK\\Configuration', false)) {
            $this->markTestSkipped('Cannot test missing SdkConfiguration class if it is loaded.');
        } else {
            $this->expectException(\Symfony\Component\Config\Exception\FileLocatorFileNotFoundException::class);
            SdkAutoloader::autoload();
        }
    }

    // Additional tests for environment and file-based initializers, and instrumentation registration,
    // would require extensive mocking of OpenTelemetry internals and are best covered with integration tests.
}
