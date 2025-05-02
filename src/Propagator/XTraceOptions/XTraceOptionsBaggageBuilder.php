<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Propagator\XTraceOptions;

use OpenTelemetry\API\Baggage\BaggageBuilderInterface;
use OpenTelemetry\API\Baggage\BaggageInterface;
use OpenTelemetry\API\Baggage\Entry;
use OpenTelemetry\API\Baggage\Metadata;
use OpenTelemetry\API\Baggage\MetadataInterface;

final class XTraceOptionsBaggageBuilder implements BaggageBuilderInterface
{
    public function __construct(private array $entries = [])
    {
    }

    public function remove(string $key): BaggageBuilderInterface
    {
        unset($this->entries[$key]);

        return $this;
    }

    public function set(string $key, mixed $value, ?MetadataInterface $metadata = null): BaggageBuilderInterface
    {
        if ($key === '') {
            return $this;
        }
        $metadata ??= Metadata::getEmpty();

        $this->entries[$key] = new Entry($value, $metadata);

        return $this;
    }

    public function build(): BaggageInterface
    {
        return new XTraceOptionsBaggage($this->entries);
    }
}
