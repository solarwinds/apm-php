<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Common\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    public function test_can_instantiate()
    {
        $this->assertInstanceOf(Configuration::class, new Configuration('', '', '', null, false, []));
    }

    public function test_constructor_sets_properties()
    {
        $config = new Configuration(
            'service-name',
            'collector-url',
            'token',
            true,
            false,
            ['setting1' => 'value1']
        );
        $this->assertEquals('service-name', $config->getService());
        $this->assertEquals('collector-url', $config->getCollector());
        $this->assertEquals('token', $config->getToken());
        $this->assertTrue($config->getTracingMode());
        $this->assertFalse($config->isTriggerTraceEnabled());
        $this->assertEquals(['setting1' => 'value1'], $config->getTransactionSettings());
    }

    public function test_setters_and_getters()
    {
        $config = new Configuration('', '', '', null, false, []);
        $config->setService('svc');
        $this->assertEquals('svc', $config->getService());
        $config->setCollector('coll');
        $this->assertEquals('coll', $config->getCollector());
        $config->setToken('token');
        $this->assertEquals('token', $config->getToken());
        $config->setTracingMode(true);
        $this->assertTrue($config->getTracingMode());
        $config->setTracingMode(null);
        $this->assertNull($config->getTracingMode());
        $config->setTriggerTraceEnabled(true);
        $this->assertTrue($config->isTriggerTraceEnabled());
        $config->setTransactionSettings(['x' => 1]);
        $this->assertEquals(['x' => 1], $config->getTransactionSettings());
    }

    public function test_to_string_method()
    {
        $config = new Configuration('svc', 'coll', 'token', false, true, ['s' => 'v']);
        $str = (string) $config;
        $this->assertStringContainsString('Configuration(service=svc, collector=coll', $str);
        $this->assertStringContainsString('tracingMode=false', $str);
        $this->assertStringContainsString('triggerTraceEnabled=true', $str);
        $this->assertStringContainsString('transactionSettings={"s":"v"}', $str);
    }

    public function test_to_string_with_nulls()
    {
        $config = new Configuration('', '', '', null, false, []);
        $str = (string) $config;
        $this->assertStringContainsString('tracingMode=null', $str);
    }
}
