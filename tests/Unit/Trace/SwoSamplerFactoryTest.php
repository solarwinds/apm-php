<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace;

use InvalidArgumentException;
use OpenTelemetry\SDK\Common\Configuration\KnownValues as Values;
use OpenTelemetry\SDK\Common\Configuration\Variables as Env;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
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
}
