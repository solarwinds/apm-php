<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Ramsey\Uuid\Uuid;
use Solarwinds\ApmPhp\Resource\Detectors\Uams;

#[CoversClass(Uams::class)]
class UamsTest extends TestCase
{
    private ClientInterface&MockObject $clientMock;
    private RequestFactoryInterface&MockObject $requestFactoryMock;
    private string $fileId;
    private string $apiId;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $this->fileId = Uuid::uuid4()->toString();
        $this->apiId = Uuid::uuid4()->toString();
    }

    private function createStreamMock(string $content): StreamInterface&MockObject
    {
        $streamMock = $this->getMockBuilder(StreamInterface::class)->getMock();
        $streamMock->method('getContents')->willReturn($content);

        return $streamMock;
    }

    public function test_detects_id_from_file_when_file_present_and_api_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testDetectsIdFromFileWhenFilePresentAndApiRunning';
        file_put_contents($uamsClientIdFile, $this->fileId);

        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->requestFactoryMock
            ->method('createRequest')
            ->with('GET', 'http://127.0.0.1:2113/info/uamsclient')
            ->willReturn($requestMock);
        $responseMock
            ->method('getBody')
            ->willReturn($this->createStreamMock(json_encode([
                'is_registered' => false,
                'otel_endpoint_access' => false,
                'uamsclient_id' => $this->apiId,
                'usc_connectivity' => true])));
        $this->clientMock
            ->method('sendRequest')
            ->with($requestMock)
            ->willReturn($responseMock);

        $resource = (new Uams($uamsClientIdFile, $this->clientMock, $this->requestFactoryMock))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'sw.uams.client.id' => $this->fileId,
             ResourceAttributes::HOST_ID => $this->fileId,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());

        @unlink($uamsClientIdFile);
    }

    public function test_detects_id_from_file_when_file_present_and_api_not_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testDetectsIdFromFileWhenFilePresentAndApiNotRunning';
        file_put_contents($uamsClientIdFile, $this->fileId);

        $resource = (new Uams($uamsClientIdFile))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'sw.uams.client.id' => $this->fileId,
            ResourceAttributes::HOST_ID => $this->fileId,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());

        @unlink($uamsClientIdFile);
    }

    public function test_detects_id_from_file_when_file_present_and_unrelated_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testDetectsIdFromFileWhenFilePresentAndUnrelatedRunning';
        file_put_contents($uamsClientIdFile, $this->fileId);

        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->requestFactoryMock
            ->method('createRequest')
            ->with('GET', 'http://127.0.0.1:2113/info/uamsclient')
            ->willReturn($requestMock);
        $responseMock
            ->method('getBody')
            ->willReturn($this->createStreamMock('I am not a valid json'));
        $this->clientMock
            ->method('sendRequest')
            ->with($requestMock)
            ->willReturn($responseMock);

        $resource = (new Uams($uamsClientIdFile, $this->clientMock, $this->requestFactoryMock))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'sw.uams.client.id' => $this->fileId,
            ResourceAttributes::HOST_ID => $this->fileId,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());

        @unlink($uamsClientIdFile);
    }

    public function test_detects_id_from_api_when_file_not_present_and_api_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_not_present';

        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->requestFactoryMock
            ->method('createRequest')
            ->with('GET', 'http://127.0.0.1:2113/info/uamsclient')
            ->willReturn($requestMock);
        $responseMock
            ->method('getBody')
            ->willReturn($this->createStreamMock(json_encode([
                'is_registered' => false,
                'otel_endpoint_access' => false,
                'uamsclient_id' => $this->apiId,
                'usc_connectivity' => true])));
        $this->clientMock
            ->method('sendRequest')
            ->with($requestMock)
            ->willReturn($responseMock);

        $resource = (new Uams($uamsClientIdFile, $this->clientMock, $this->requestFactoryMock))->getResource();

        $this->assertEquals(ResourceInfo::create(Attributes::create([
            'sw.uams.client.id' => $this->apiId,
            ResourceAttributes::HOST_ID => $this->apiId,
        ]), ResourceAttributes::SCHEMA_URL)->getAttributes(), $resource->getAttributes());
    }

    public function test_detects_nothing_when_file_not_present_and_api_not_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_not_present';
        $resource = (new Uams($uamsClientIdFile))->getResource();
        $this->assertEquals(ResourceInfoFactory::emptyResource(), $resource);
    }

    public function test_detects_nothing_when_file_not_present_and_unrelated_running(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_not_present';

        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $this->requestFactoryMock
            ->method('createRequest')
            ->with('GET', 'http://127.0.0.1:2113/info/uamsclient')
            ->willReturn($requestMock);
        $responseMock
            ->method('getBody')
            ->willReturn($this->createStreamMock('I am not a valid json'));
        $this->clientMock
            ->method('sendRequest')
            ->with($requestMock)
            ->willReturn($responseMock);

        $resource = (new Uams($uamsClientIdFile, $this->clientMock, $this->requestFactoryMock))->getResource();
        $this->assertEquals(ResourceInfoFactory::emptyResource(), $resource);
    }
}
