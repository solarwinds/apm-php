<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\API\Baggage\BaggageBuilderInterface;
use OpenTelemetry\API\Baggage\BaggageInterface;
use OpenTelemetry\API\Baggage\Entry;
use OpenTelemetry\API\Baggage\Metadata;
use OpenTelemetry\API\Baggage\MetadataInterface;

final class XTraceOptionsResponseBaggageBuilder implements BaggageBuilderInterface
{
    /** @param array<string, Entry> $entries */
    public function __construct(private array $entries = [])
    {
    }

    /** @inheritDoc */
    public function remove(string $key): BaggageBuilderInterface
    {
        unset($this->entries[$key]);

        return $this;
    }

    /** @inheritDoc */
    public function set(string $key, $value, ?MetadataInterface $metadata = null): BaggageBuilderInterface
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
        return new XTraceOptionsResponseBaggage($this->entries);
    }
}
