<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Propagator\XTraceOptions;

use OpenTelemetry\API\Baggage\BaggageBuilderInterface;
use OpenTelemetry\API\Baggage\BaggageInterface;
use OpenTelemetry\API\Baggage\Entry;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ScopeInterface;

final class XTraceOptionsBaggage implements BaggageInterface
{
    private static ?self $emptyBaggage = null;

    public function __construct(private readonly array $entries = [])
    {
    }

    #[\Override]
    public static function getBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder();
    }

    #[\Override]
    public function activate(): ScopeInterface
    {
        return Context::getCurrent()->withContextValue($this)->activate();
    }

    #[\Override]
    public static function getCurrent(): BaggageInterface
    {
        return self::fromContext(Context::getCurrent());
    }

    #[\Override]
    public static function fromContext(ContextInterface $context): BaggageInterface
    {
        return $context->get(SwoContextKeys::xtraceoptions()) ?? self::getEmpty();
    }

    #[\Override]
    public static function getEmpty(): BaggageInterface
    {
        if (!self::$emptyBaggage instanceof \Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggage) {
            self::$emptyBaggage = new self();
        }

        return self::$emptyBaggage;
    }

    #[\Override]
    public function getValue(string $key)
    {
        if (($entry = $this->getEntry($key)) instanceof \OpenTelemetry\API\Baggage\Entry) {
            return $entry->getValue();
        }

        return null;
    }

    #[\Override]
    public function getEntry(string $key): ?Entry
    {
        return $this->entries[$key] ?? null;
    }

    #[\Override]
    public function getAll(): iterable
    {
        foreach ($this->entries as $key => $entry) {
            yield $key => $entry;
        }
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    #[\Override]
    public function toBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder($this->entries);
    }

    #[\Override]
    public function storeInContext(ContextInterface $context): ContextInterface
    {
        return $context->with(SwoContextKeys::xtraceoptions(), $this);
    }

}
