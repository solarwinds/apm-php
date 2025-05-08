<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Resource\Detectors;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Solarwinds\ApmPhp\Resource\Detectors\Uams;

#[CoversClass(Uams::class)]
class UamsTest extends TestCase
{
    private $clientMock;
    private $requestFactoryMock;
    private $fileId;
    private $apiId;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $this->fileId = Uuid::uuid4()->toString();
        $this->apiId = Uuid::uuid4()->toString();
    }

    private function createStreamMock(string $content)
    {
        $streamMock = $this->getMockBuilder('Psr\Http\Message\StreamInterface')->getMock();
        $streamMock->method('getContents')->willReturn($content);
        return $streamMock;
    }

    public function testDetectsIdFromFileWhenFilePresentAndApiRunning(): void
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

    public function testDetectsIdFromFileWhenFilePresentAndApiNotRunning(): void
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

    public function testDetectsIdFromFileWhenFilePresentAndUnrelatedRunning(): void
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
            ->willReturn($this->createStreamMock("I am not a valid json"));
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

    public function testDetectsIdFromApiWhenFileNotPresentAndApiRunning(): void
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

    public function testDetectsNothingWhenFileNotPresentAndApiNotRunning(): void
    {
        $uamsClientIdFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file_not_present';
        $resource = (new Uams($uamsClientIdFile))->getResource();
        $this->assertEquals(ResourceInfo::emptyResource(), $resource);
    }

    public function testDetectsNothingWhenFileNotPresentAndUnrelatedRunning(): void
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
            ->willReturn($this->createStreamMock("I am not a valid json"));
        $this->clientMock
            ->method('sendRequest')
            ->with($requestMock)
            ->willReturn($responseMock);

        $resource = (new Uams($uamsClientIdFile, $this->clientMock, $this->requestFactoryMock))->getResource();
        $this->assertEquals(ResourceInfo::emptyResource(), $resource);
    }
}