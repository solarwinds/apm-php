<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

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

    public static function getBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder();
    }

    public function activate(): ScopeInterface
    {
        return Context::getCurrent()->withContextValue($this)->activate();
    }

    public static function getCurrent(): BaggageInterface
    {
        return self::fromContext(Context::getCurrent());
    }

    public static function fromContext(ContextInterface $context): BaggageInterface
    {
        return $context->get(SwoContextKeys::xtraceoptions()) ?? self::getEmpty();
    }

    public static function getEmpty(): BaggageInterface
    {
        if (null === self::$emptyBaggage) {
            self::$emptyBaggage = new self();
        }

        return self::$emptyBaggage;
    }

    public function getValue(string $key)
    {
        if (($entry = $this->getEntry($key)) !== null) {
            return $entry->getValue();
        }

        return null;
    }

    public function getEntry(string $key): ?Entry
    {
        return $this->entries[$key] ?? null;
    }

    public function getAll(): iterable
    {
        foreach ($this->entries as $key => $entry) {
            yield $key => $entry;
        }
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function toBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder($this->entries);
    }

    public function storeInContext(ContextInterface $context): ContextInterface
    {
        return $context->with(SwoContextKeys::xtraceoptions(), $this);
    }

}
