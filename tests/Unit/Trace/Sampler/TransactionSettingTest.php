<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use Closure;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\TransactionSetting;

class TransactionSettingTest extends TestCase
{
    public function test_constructor_and_getters(): void
    {
        $matcher = function ($input) { return $input === 'foo'; };
        $setting = new TransactionSetting(true, $matcher);
        $this->assertTrue($setting->getTracing());
        $this->assertInstanceOf(Closure::class, $setting->getMatcher());
        $this->assertTrue(($setting->getMatcher())('foo'));
        $this->assertFalse(($setting->getMatcher())('bar'));
    }

    public function test_setters(): void
    {
        $matcher1 = function ($input) { return $input === 'foo'; };
        $matcher2 = function ($input) { return $input === 'bar'; };
        $setting = new TransactionSetting(false, $matcher1);
        $setting->setTracing(true);
        $this->assertTrue($setting->getTracing());
        $setting->setMatcher($matcher2);
        $this->assertTrue(($setting->getMatcher())('bar'));
        $this->assertFalse(($setting->getMatcher())('foo'));
    }
}
