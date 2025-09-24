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
        $this->assertInstanceOf(Configuration::class, new Configuration(true, '', '', [], null, false, null, []));
    }

    public function test_constructor_sets_properties()
    {
        $transactionName = function () { return 'txn'; };
        $config = new Configuration(
            true,
            'service-name',
            'collector-url',
            ['header1' => 'value1'],
            true,
            false,
            $transactionName,
            ['setting1' => 'value1']
        );
        $this->assertTrue($config->getEnabled());
        $this->assertEquals('service-name', $config->getService());
        $this->assertEquals('collector-url', $config->getCollector());
        $this->assertEquals(['header1' => 'value1'], $config->getHeaders());
        $this->assertTrue($config->getTracingMode());
        $this->assertFalse($config->isTriggerTraceEnabled());
        $transactionNameClosure = $config->getTransactionName();
        $this->assertNotNull($transactionNameClosure);
        if ($transactionNameClosure !== null) {
            $this->assertEquals('txn', $transactionNameClosure());
        }
        $this->assertEquals(['setting1' => 'value1'], $config->getTransactionSettings());
    }

    public function test_set_enabled()
    {
        $config = new Configuration(true, '', '', [], null, false, null, []);
        $config->setEnabled(false);
        $this->assertFalse($config->getEnabled());
    }
}
