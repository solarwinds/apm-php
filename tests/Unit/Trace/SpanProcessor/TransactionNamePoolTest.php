<?php

declare(strict_types=1);

namespace Tests\Unit\Trace\SpanProcessor;

use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNamePool;

final class TransactionNamePoolTest extends TestCase
{
    private TransactionNamePool $pool;

    protected function setUp(): void
    {
        $this->pool = new TransactionNamePool();
    }

    public function test_register_name(): void
    {
        $name = 'test_name';
        $registeredName = $this->pool->register($name);

        $this->assertSame($name, $registeredName);
        $this->assertArrayHasKey($name, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(1, $this->getPrivateProperty($this->pool, 'minHeap'));
    }

    public function test_register_name_exceeds_max_length(): void
    {
        $longName = str_repeat('a', 300); // Exceeds default max length of 256
        $registeredName = $this->pool->register($longName);

        $this->assertSame(substr($longName, 0, 256), $registeredName);
        $this->assertArrayHasKey($registeredName, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(1, $this->getPrivateProperty($this->pool, 'minHeap'));
    }

    public function test_register_name_exceeds_max_size(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $name = 'name_' . $i;
            $this->pool->register($name);
        }

        $this->assertCount(200, $this->getPrivateProperty($this->pool, 'pool'));

        $defaultName = $this->pool->register('new_name');
        $this->assertSame('other', $defaultName);

        $registeredName = $this->pool->register('name_1');
        $this->assertSame('name_1', $registeredName);
    }

    public function test_housekeep(): void
    {
        $this->pool = new TransactionNamePool(200, 1);
        $name = 'test_name';
        $this->pool->register($name);

        sleep(2);

        $this->pool->housekeeping();

        $this->assertArrayNotHasKey($name, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(0, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(0, $this->getPrivateProperty($this->pool, 'minHeap'));
    }

    public function test_register_again(): void
    {
        $name = 'test_name';
        $this->pool->register($name);

        sleep(2);

        $this->pool->register($name);

        $this->assertArrayHasKey($name, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(1, $this->getPrivateProperty($this->pool, 'pool'));
        $this->assertCount(1, $this->getPrivateProperty($this->pool, 'minHeap'));
    }

    private function getPrivateProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

}
