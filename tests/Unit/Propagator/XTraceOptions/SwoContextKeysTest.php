<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Propagator\XTraceOptions;

use OpenTelemetry\Context\ContextKeyInterface;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\SwoContextKeys;

class SwoContextKeysTest extends TestCase
{
    public function test_xtraceoptions_returns_context_key_interface(): void
    {
        $key1 = SwoContextKeys::xtraceoptions();
        $key2 = SwoContextKeys::xtraceoptions();
        $this->assertInstanceOf(ContextKeyInterface::class, $key1);
        $this->assertSame($key1, $key2, 'Should return the same instance (singleton)');
    }
}
