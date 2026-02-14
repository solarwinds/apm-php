<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Resource\Detectors;

use Exception;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use Solarwinds\ApmPhp\Common\Configuration\Variables;

final class K8s implements ResourceDetectorInterface
{
    use LogsMessagesTrait;

    private const NAMESPACE_FILE_WINDOWS = 'C:\\var\\run\\secrets\\kubernetes.io\\serviceaccount\\namespace';
    private const NAMESPACE_FILE_LINUX = '/run/secrets/kubernetes.io/serviceaccount/namespace';
    private const MOUNTINFO_FILE = '/proc/self/mountinfo';
    private const UID_REGEX = '/[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}/i';
    private string $namespaceFile;
    private string $mountInfoFile;

    public function __construct(
        ?string $namespaceFile = null,
        ?string $mountInfoFile = null,
    ) {
        $this->namespaceFile = $namespaceFile ?? (PHP_OS_FAMILY === 'Windows' ? self::NAMESPACE_FILE_WINDOWS : self::NAMESPACE_FILE_LINUX);
        $this->mountInfoFile = $mountInfoFile ?? self::MOUNTINFO_FILE;
    }

    /**
     * Returns resource attributes related to the current Kubernetes pod such as namespace, UID, and name.
     *
     * @return ResourceInfo The resource information for the current pod, or an empty resource if the k8s namespace is not available.
     */
    public function getResource(): ResourceInfo
    {
        $attributes = [];

        $namespace = $this->getPodNamespace();
        if ($namespace !== null) {
            $attributes[ResourceAttributes::K8S_NAMESPACE_NAME] = $namespace;
        } else {
            // Namespace is required for Kubernetes resources
            return ResourceInfoFactory::emptyResource();
        }

        $uid = $this->getPodUid();
        if ($uid !== null) {
            $attributes[ResourceAttributes::K8S_POD_UID] = $uid;
        }

        $name = $this->getPodName();
        if ($name) {
            $attributes[ResourceAttributes::K8S_POD_NAME] = $name;
        }

        return ResourceInfo::create(Attributes::create($attributes), ResourceAttributes::SCHEMA_URL);
    }

    private function getPodName(): string
    {
        if (Configuration::has(Variables::SW_K8S_POD_NAME)) {
            $env = Configuration::getString(Variables::SW_K8S_POD_NAME);
            $this->logDebug('Read pod name from environment variable');

            return $env;
        }

        return php_uname('n');
    }

    private function getPodUid(): ?string
    {
        if (Configuration::has(Variables::SW_K8S_POD_UID)) {
            $env = Configuration::getString(Variables::SW_K8S_POD_UID);
            $this->logDebug('Read pod UID from environment variable');

            return $env;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->logDebug('Cannot read pod UID on Windows');

            return null;
        }

        if (!file_exists($this->mountInfoFile)) {
            $this->logDebug('Mount info file not found');

            return null;
        }

        try {
            $lines = file($this->mountInfoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                $this->logDebug('Cannot read mount info file');

                return null;
            }
            foreach ($lines as $line) {
                $fields = preg_split('/\s+/', $line);
                if (count($fields) < 10) {
                    continue;
                }

                [$identity, $parentId, , $root] = array_slice($fields, 0, 4);
                if (!ctype_digit((string) $identity) || !ctype_digit((string) $parentId)) {
                    continue;
                }

                if (!str_contains((string) $root, 'kube')) {
                    continue;
                }

                if (preg_match(self::UID_REGEX, (string) $root, $matches)) {
                    return $matches[0];
                }
            }
        } catch (Exception $e) {
            $this->logDebug('Error reading pod UID', ['exception' => $e]);
        }

        $this->logDebug('Cannot read pod UID');

        return null;
    }

    private function getPodNamespace(): ?string
    {
        if (Configuration::has(Variables::SW_K8S_POD_NAMESPACE)) {
            $env = Configuration::getString(Variables::SW_K8S_POD_NAMESPACE);
            $this->logDebug('Read pod namespace from environment variable');

            return $env;
        }
        if (!file_exists($this->namespaceFile)) {
            $this->logDebug('Namespace file not found');

            return null;
        }
        $namespace = file_get_contents($this->namespaceFile);
        if ($namespace !== false) {
            $this->logDebug('Read pod namespace from file');

            return trim($namespace);
        }
        $this->logDebug('Cannot read pod namespace');

        return null;
    }
}
