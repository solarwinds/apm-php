<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Benchmark;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\JsonSampler;

class JsonSamplerSpanCreationBench
{
    private readonly TracerInterface $tracer;
    private readonly SamplerInterface $sampler;
    private readonly ResourceInfo $resource;

    public function __construct()
    {
        $path = sys_get_temp_dir() . '/benchmark-settings.json';
        file_put_contents($path, json_encode([
            [
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time(),
                'ttl' => 60,
            ],
        ]));
        $this->sampler = new JsonSampler(null, new Configuration(true, 'test', '', '', [], true, true, null, []), $path);
        $this->resource = ResourceInfo::create(Attributes::create([
            'sw.data.module' => 'apm',
            'sw.apm.version' => '0.0.0',
            ResourceAttributes::SERVICE_NAME => 'apm-php-benchmark',
        ]));
        $processor = new NoopSpanProcessor();
        $provider = new TracerProvider($processor, $this->sampler, $this->resource);
        $this->tracer = $provider->getTracer('apm-php-benchmark');
    }

    /**
     * @Revs(1000)
     * @Iterations(10)
     * @OutputTimeUnit("microseconds")
     */
    public function benchCreateSpans(): void
    {
        $span = $this->tracer->spanBuilder('foo')
            ->setAttribute('foo', PHP_INT_MAX)
            ->startSpan();
        $span->addEvent('my_event');
        $span->end();
    }
}
