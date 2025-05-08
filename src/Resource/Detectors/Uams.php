<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class Uams implements ResourceDetectorInterface
{
    use LogsMessagesTrait;

    private const UAMS_CLIENT_PATH_WINDOWS = 'C:\\ProgramData\\SolarWinds\\UAMSClient\\uamsclientid';
    private const UAMS_CLIENT_PATH_LINUX = '/opt/solarwinds/uamsclient/var/uamsclientid';
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
        $this->uamsClientIdFile = $uamsClientIdFile ?? (PHP_OS_FAMILY === 'Windows' ? self::UAMS_CLIENT_PATH_WINDOWS : self::UAMS_CLIENT_PATH_LINUX);
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
    }

    private function readFromFile(): ?string
    {
        if (!file_exists($this->uamsClientIdFile)) {
            $this->logDebug('File not found: ' . $this->uamsClientIdFile);

            return null;
        }
        $content = file_get_contents($this->uamsClientIdFile);
        if ($content === false) {
            $this->logDebug('Unable to read file: ' . $this->uamsClientIdFile);

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

                return null;
            }

            return $data[self::UAMS_CLIENT_ID_FIELD];
        } catch (ClientExceptionInterface $e) {
            $this->logDebug('API request error' . $e);

            return null;
        }
    }

    /**
     * Returns resource attributes related to the current uams client id.
     *
     * @return ResourceInfo The resource information for the current uams client id, or an empty resource if the uams client is not available.
     */
    public function getResource(): ResourceInfo
    {
        $id = $this->readFromFile();
        $id = $id ?? $this->readFromApi();
        if ($id === null) {
            return ResourceInfoFactory::emptyResource();
        }
        $attributes = [
            self::ATTR_UAMS_CLIENT_ID => $id,
            ResourceAttributes::HOST_ID => $id,
        ];

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }

}
