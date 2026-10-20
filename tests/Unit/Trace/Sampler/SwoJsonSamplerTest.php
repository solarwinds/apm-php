<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Trace\SamplingResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\SwoJsonSampler;

#[CoversClass(SwoJsonSampler::class)]
class SwoJsonSamplerTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'swo-json-sampler-test-');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function test_get_description_includes_parent_based_json_sampler(): void
    {
        $sampler = new SwoJsonSampler(null, $this->configuration(), $this->path);

        $this->assertSame('ParentBased+JSON Sampler (' . $this->path . ')', $sampler->getDescription());
    }

    public function test_should_sample_uses_json_sampler_result(): void
    {
        file_put_contents($this->path, json_encode([
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

        $sampler = new SwoJsonSampler(null, $this->configuration(), $this->path);
        $result = $sampler->shouldSample(
            Context::getCurrent(),
            str_repeat('a', 32),
            'test-span',
            1,
            Attributes::create([]),
            [],
        );

        $this->assertSame(SamplingResult::RECORD_AND_SAMPLE, $result->getDecision());
    }

    private function configuration(): Configuration
    {
        return new Configuration(
            service: 'test',
            collector: '',
            token: '',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: [],
        );
    }
}
