<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class Uams implements ResourceDetectorInterface
{
    use LogsMessagesTrait;

    private const UAMS_CLIENT_PATH = PHP_OS_FAMILY === 'Windows'
        ? 'C:\\ProgramData\\SolarWinds\\UAMSClient\\uamsclientid'
    : '/opt/solarwinds/uamsclient/var/uamsclientid';
    private const UAMS_CLIENT_URL = 'http://127.0.0.1:2113/info/uamsclient';
    private const UAMS_CLIENT_ID_FIELD = 'uamsclient_id';
    private string $uamsClientIdFile;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    private const ATTR_UAMS_CLIENT_ID = 'sw.uams.client.id';

    public function __construct(
        ?string $uamsClientIdFile = null,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
    ) {
        $this->uamsClientIdFile = $uamsClientIdFile ?: self::UAMS_CLIENT_PATH;
        $this->client = $client ?: Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    private function readFromFile(): ?string
    {
        if (!file_exists($this->uamsClientIdFile)) {
            $this->logDebug('File not found: ' . $this->uamsClientIdFile);

            return null;
        }
        $content = file_get_contents($this->uamsClientIdFile);
        if ($content === false) {
            return null;
        }

        return trim($content);
    }

    private function readFromApi(): ?string
    {
        try {
            $req = $this->requestFactory->createRequest('GET', self::UAMS_CLIENT_URL);
            $res = $this->client->sendRequest($req);
            $data = json_decode($res->getBody()->getContents(), true);
            if (!is_array($data) || !isset($data[self::UAMS_CLIENT_ID_FIELD]) || !is_string($data[self::UAMS_CLIENT_ID_FIELD])) {
                $this->logDebug('Invalid response format');
            }

            return $data[self::UAMS_CLIENT_ID_FIELD];
        } catch (ClientExceptionInterface $e) {
            $this->logDebug('API request error' . $e);

            return null;
        }
    }

    public function getResource(): ResourceInfo
    {
        $id = $this->readFromFile();
        $id = $id ?? $this->readFromApi();
        if ($id === null) {
            return ResourceInfo::emptyResource();
        }
        $attributes = [
            self::ATTR_UAMS_CLIENT_ID => $id,
            ResourceAttributes::HOST_ID => $id,
        ];

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }

}
