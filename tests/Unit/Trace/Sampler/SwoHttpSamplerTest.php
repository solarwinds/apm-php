<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Trace\SamplingResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Trace\Sampler\CacheExtensionInterface;
use Solarwinds\ApmPhp\Trace\Sampler\SwoHttpSampler;

#[CoversClass(SwoHttpSampler::class)]
class SwoHttpSamplerTest extends TestCase
{
    public function test_get_description_includes_parent_based_http_sampler(): void
    {
        $sampler = new SwoHttpSampler(
            null,
            $this->configuration(),
            client: $this->createMock(ClientInterface::class),
            requestFactory: $this->createMock(RequestFactoryInterface::class),
        );

        $this->assertSame('ParentBased+HTTP Sampler (apm.collector.na-01.cloud.solarwinds.com)', $sampler->getDescription());
    }

    public function test_should_sample_uses_http_sampler_result(): void
    {
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer test-token')
            ->willReturn($request);

        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', 'https://apm.collector.na-01.cloud.solarwinds.com/v1/settings/test+service/' . urlencode(gethostname()))
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->method('getBody')->willReturn($this->createConfiguredMock(StreamInterface::class, [
            'getContents' => json_encode([
                'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
                'value' => 1000000,
                'arguments' => [
                    'BucketCapacity' => 100,
                    'BucketRate' => 10,
                ],
                'timestamp' => time(),
                'ttl' => 60,
            ]),
        ]));

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $cacheExtension = $this->createMock(CacheExtensionInterface::class);
        $cacheExtension->method('isExtensionLoaded')->willReturn(false);

        $sampler = new SwoHttpSampler(
            null,
            $this->configuration(),
            client: $client,
            requestFactory: $requestFactory,
            cacheExtension: $cacheExtension,
        );

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
            service: 'test service',
            collector: 'https://apm.collector.na-01.cloud.solarwinds.com',
            token: 'test-token',
            tracingMode: true,
            triggerTraceEnabled: true,
            transactionSettings: [],
        );
    }
}
