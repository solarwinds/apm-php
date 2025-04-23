<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\API\Baggage\BaggageBuilderInterface;
use OpenTelemetry\API\Baggage\BaggageInterface;
use OpenTelemetry\API\Baggage\Entry;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ScopeInterface;
use Override;

final class XTraceOptionsBaggage implements BaggageInterface
{
    private static ?self $emptyBaggage = null;

    /** @param array<string, Entry> $entries */
    public function __construct(private readonly array $entries = [])
    {
    }

    /** @inheritDoc */
    public static function getBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder();
    }

    public function activate(): ScopeInterface
    {
        return Context::getCurrent()->withContextValue($this)->activate();
    }

    /** @inheritDoc */
    public static function getCurrent(): BaggageInterface
    {
        return self::fromContext(Context::getCurrent());
    }

    /** @inheritDoc */
    public static function fromContext(ContextInterface $context): BaggageInterface
    {
        return $context->get(SwoContextKeys::xtraceoptions()) ?? self::getEmpty();
    }

    /** @inheritDoc */
    public static function getEmpty(): BaggageInterface
    {
        if (null === self::$emptyBaggage) {
            self::$emptyBaggage = new self();
        }

        return self::$emptyBaggage;
    }

    /** @inheritDoc */
    public function getValue(string $key)
    {
        if (($entry = $this->getEntry($key)) !== null) {
            return $entry->getValue();
        }

        return null;
    }

    /** @inheritDoc */
    public function getEntry(string $key): ?Entry
    {
        return $this->entries[$key] ?? null;
    }

    /** @inheritDoc */
    public function getAll(): iterable
    {
        foreach ($this->entries as $key => $entry) {
            yield $key => $entry;
        }
    }

    /** @inheritDoc */
    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /** @inheritDoc */
    public function toBuilder(): BaggageBuilderInterface
    {
        return new XTraceOptionsBaggageBuilder($this->entries);
    }

    /** @inheritDoc */
    #[Override]
    public function storeInContext(ContextInterface $context): ContextInterface
    {
        return $context->with(SwoContextKeys::xtraceoptions(), $this);
    }

}
