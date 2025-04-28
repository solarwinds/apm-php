<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class Settings
{
    public function __construct(
        public int $sampleRate,
        public SampleSource $sampleSource,
        public int $flags,
        public array $buckets,
        public ?string $signatureKey,
        public int $timestamp,
        public int $ttl,
    ) {
    }

    public static function merge(Settings $remote, LocalSettings $local): Settings
    {
        $flags = $local->getTracingMode()?->value ?? $remote->flags;

        if ($local->getTriggerMode()) {
            $flags |= Flags::TRIGGERED_TRACE->value;
        } else {
            $flags &= ~Flags::TRIGGERED_TRACE->value;
        }

        if ($remote->flags & Flags::OVERRIDE->value) {
            $flags &= $remote->flags;
            $flags |= Flags::OVERRIDE->value;
        }

        return new Settings(
            $remote->sampleRate,
            $remote->sampleSource,
            $flags,
            $remote->buckets,
            $remote->signatureKey,
            $remote->timestamp,
            $remote->ttl
        );
    }

    public function __toString(): string
    {
        return json_encode([
            'sampleRate' => $this->sampleRate,
            'sampleSource' => $this->sampleSource,
            'flags' => $this->flags,
            'buckets' => $this->buckets,
            'signatureKey' => $this->signatureKey,
            'timestamp' => $this->timestamp,
            'ttl' => $this->ttl,
        ]);
    }
}
