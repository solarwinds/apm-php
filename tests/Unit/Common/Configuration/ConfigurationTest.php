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
        $this->assertInstanceOf(Configuration::class, new Configuration(true, '', '', '', [], null, false, null, []));
    }

    public function test_constructor_sets_properties()
    {
        $config = new Configuration(
            true,
            'service-name',
            'collector-url',
            'token',
            ['header1' => 'value1'],
            true,
            false,
            'txn',
            ['setting1' => 'value1']
        );
        $this->assertTrue($config->getEnabled());
        $this->assertEquals('service-name', $config->getService());
        $this->assertEquals('collector-url', $config->getCollector());
        $this->assertEquals('token', $config->getToken());
        $this->assertEquals(['header1' => 'value1'], $config->getHeaders());
        $this->assertTrue($config->getTracingMode());
        $this->assertFalse($config->isTriggerTraceEnabled());
        $this->assertEquals('txn', $config->getTransactionName());
        $this->assertEquals(['setting1' => 'value1'], $config->getTransactionSettings());
    }

    public function test_set_enabled()
    {
        $config = new Configuration(true, '', '', '', [], null, false, null, []);
        $config->setEnabled(false);
        $this->assertFalse($config->getEnabled());
    }

    public function test_setters_and_getters()
    {
        $config = new Configuration(false, '', '', '', [], null, false, null, []);
        $config->setEnabled(true);
        $this->assertTrue($config->getEnabled());
        $config->setService('svc');
        $this->assertEquals('svc', $config->getService());
        $config->setCollector('coll');
        $this->assertEquals('coll', $config->getCollector());
        $config->setToken('token');
        $this->assertEquals('token', $config->getToken());
        $config->setHeaders(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $config->getHeaders());
        $config->setTracingMode(true);
        $this->assertTrue($config->getTracingMode());
        $config->setTracingMode(null);
        $this->assertNull($config->getTracingMode());
        $config->setTriggerTraceEnabled(true);
        $this->assertTrue($config->isTriggerTraceEnabled());
        $config->setTransactionName('txn');
        $this->assertSame('txn', $config->getTransactionName());
        $config->setTransactionName(null);
        $this->assertNull($config->getTransactionName());
        $config->setTransactionSettings(['x' => 1]);
        $this->assertEquals(['x' => 1], $config->getTransactionSettings());
    }

    public function test_to_string_method()
    {
        $config = new Configuration(true, 'svc', 'coll', 'token', ['h' => 'v'], false, true, 'txn', ['s' => 'v']);
        $str = (string) $config;
        $this->assertStringContainsString('Configuration(enabled=true, service=svc, collector=coll', $str);
        $this->assertStringContainsString('headers={"h":"v"}', $str);
        $this->assertStringContainsString('tracingMode=false', $str);
        $this->assertStringContainsString('triggerTraceEnabled=true', $str);
        $this->assertStringContainsString('transactionName=Closure', $str);
        $this->assertStringContainsString('transactionSettings={"s":"v"}', $str);
    }

    public function test_to_string_with_nulls()
    {
        $config = new Configuration(false, '', '', '', [], null, false, null, []);
        $str = (string) $config;
        $this->assertStringContainsString('enabled=false', $str);
        $this->assertStringContainsString('tracingMode=null', $str);
        $this->assertStringContainsString('transactionName=null', $str);
    }
}
