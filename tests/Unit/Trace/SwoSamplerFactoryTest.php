<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace;

use InvalidArgumentException;
use OpenTelemetry\SDK\Common\Configuration\KnownValues as Values;
use OpenTelemetry\SDK\Common\Configuration\Variables as Env;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\SwoSamplerFactory;

class SwoSamplerFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset environment variables before each test
        \putenv(Env::OTEL_TRACES_SAMPLER);
        \putenv(Env::OTEL_TRACES_SAMPLER_ARG);
    }
    protected function tearDown(): void
    {
        // Reset environment variables after each test
        \putenv(Env::OTEL_TRACES_SAMPLER);
        \putenv(Env::OTEL_TRACES_SAMPLER_ARG);
    }

    public function test_always_on_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_ALWAYS_ON);
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(AlwaysOnSampler::class, $sampler);
    }

    public function test_always_off_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_ALWAYS_OFF);
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(AlwaysOffSampler::class, $sampler);
    }

    public function test_parent_based_always_on_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_PARENT_BASED_ALWAYS_ON);
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(ParentBased::class, $sampler);
        // Check that the root sampler is AlwaysOnSampler using reflection
        $ref = new \ReflectionClass($sampler);
        $prop = $ref->getProperty('root');
        $this->assertInstanceOf(AlwaysOnSampler::class, $prop->getValue($sampler));
    }

    public function test_parent_based_always_off_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_PARENT_BASED_ALWAYS_OFF);
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(ParentBased::class, $sampler);
        $ref = new \ReflectionClass($sampler);
        $prop = $ref->getProperty('root');
        $this->assertInstanceOf(AlwaysOffSampler::class, $prop->getValue($sampler));
    }

    public function test_trace_id_ratio_based_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_TRACE_ID_RATIO);
        \putenv(Env::OTEL_TRACES_SAMPLER_ARG . '=0.5');
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(TraceIdRatioBasedSampler::class, $sampler);
    }

    public function test_parent_based_trace_id_ratio_based_sampler(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=' . Values::VALUE_PARENT_BASED_TRACE_ID_RATIO);
        \putenv(Env::OTEL_TRACES_SAMPLER_ARG . '=0.7');
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(ParentBased::class, $sampler);
        $ref = new \ReflectionClass($sampler);
        $prop = $ref->getProperty('root');
        $this->assertInstanceOf(TraceIdRatioBasedSampler::class, $prop->getValue($sampler));
    }

    public function test_unknown_sampler_throws(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=unknown_sampler');
        $factory = new SwoSamplerFactory();
        $this->expectException(InvalidArgumentException::class);
        $factory->create();
    }

    public function test_default_values_are_used(): void
    {
        \putenv(Env::OTEL_TRACES_SAMPLER . '=solarwinds_http');
        if (!empty(getenv('SW_APM_SERVICE_KEY'))) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is set.');
        }
        // Do not set SW_APM_SERVICE_KEY
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $this->assertInstanceOf(AlwaysOffSampler::class, $sampler, $sampler->getDescription());
    }

    public function test_get_solarwinds_configuration_http(): void
    {
        $factory = new SwoSamplerFactory(ResourceInfoFactory::emptyResource());
        $serviceKey = 'token1234:myservice';
        $config = $factory->getSolarwindsConfiguration(true, $serviceKey);
        $this->assertEquals('myservice', $config->getService());
        $this->assertEquals('https://apm.collector.na-01.cloud.solarwinds.com', $config->getCollector());
        $this->assertEquals('token1234', $config->getToken());
        $this->assertTrue($config->getTracingMode());
        $this->assertTrue($config->isTriggerTraceEnabled());
        $this->assertEquals([], $config->getTransactionSettings());
    }

    public function test_get_solarwinds_configuration_json(): void
    {
        $factory = new SwoSamplerFactory();
        $config = $factory->getSolarwindsConfiguration(false);
        $this->assertEquals('', $config->getCollector());
        $this->assertEquals('', $config->getToken());
    }

    public function test_get_solarwinds_configuration_transaction_settings_file(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sw_transaction_settings');
        file_put_contents($file, json_encode([(object) ['tracing' => 'enabled', 'regex' => '/^.*.html$/'], (object) ['tracing' => 'disabled', 'regex' => '/^.*.css$/']]));
        \putenv('SW_APM_TRANSACTION_SETTINGS_FILE=' . $file);
        $factory = new SwoSamplerFactory();
        $config = $factory->getSolarwindsConfiguration(true, 'token:service');
        $this->assertEquals([['tracing' => 'enabled', 'regex' => '/^.*.html$/'], ['tracing' => 'disabled', 'regex' => '/^.*.css$/']], $config->getTransactionSettings());
        \putenv('SW_APM_TRANSACTION_SETTINGS_FILE');
        unlink($file);
    }

    public function test_get_solarwinds_configuration_transaction_settings_env(): void
    {
        \putenv('SW_APM_TRANSACTION_SETTINGS=' . json_encode([(object) ['tracing' => 'enabled', 'regex' => '/^.*.html$/'], (object) ['tracing' => 'disabled', 'regex' => '/^.*.css$/']]));
        $factory = new SwoSamplerFactory();
        $config = $factory->getSolarwindsConfiguration(true, 'token:service');
        $this->assertEquals([['tracing' => 'enabled', 'regex' => '/^.*.html$/'], ['tracing' => 'disabled', 'regex' => '/^.*.css$/']], $config->getTransactionSettings());
        \putenv('SW_APM_TRANSACTION_SETTINGS');
    }

    public function test_get_solarwinds_configuration_transaction_settings_file_end2end(): void
    {
        $serviceKey = getenv('SW_APM_SERVICE_KEY');
        if (empty($serviceKey)) {
            $this->markTestSkipped('SW_APM_SERVICE_KEY environment variable is not set.');
        }
        \putenv(Env::OTEL_TRACES_SAMPLER . '=solarwinds_http');
        // Global disable tracing mode to ensure transaction settings are applied
        \putenv('SW_APM_TRACING_MODE=disabled');
        $file = tempnam(sys_get_temp_dir(), 'sw_transaction_settings');
        file_put_contents($file, json_encode([(object) ['tracing' => 'enabled', 'regex' => '/^INTERNAL:test$/']]));
        \putenv('SW_APM_TRANSACTION_SETTINGS_FILE=' . $file);
        $factory = new SwoSamplerFactory();
        $sampler = $factory->create();
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($spanExporter))->setSampler($sampler)->build();
        $tracer = $tracerProvider->getTracer('test');
        $span = $tracer->spanBuilder('test')->startSpan();
        $this->assertTrue($span->isRecording());
        $span->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertArrayHasKey('SampleRate', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('SampleSource', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('BucketCapacity', $spans[0]->getAttributes()->toArray());
        $this->assertArrayHasKey('BucketRate', $spans[0]->getAttributes()->toArray());
        \putenv('SW_APM_TRANSACTION_SETTINGS_FILE');
        \putenv('SW_APM_TRACING_MODE');
        \putenv(Env::OTEL_TRACES_SAMPLER);
        unlink($file);
    }
}
