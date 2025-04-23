<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\SampleSource;

#[CoversClass(SampleSource::class)]
class SampleSourceTest extends TestCase
{
    public function testSampleSourceValues(): void
    {
        $this->assertEquals(2, SampleSource::LocalDefault->value);
        $this->assertEquals(6, SampleSource::Remote->value);
    }

    public function testSampleSourceEnumCases(): void
    {
        $this->assertInstanceOf(SampleSource::class, SampleSource::LocalDefault);
        $this->assertInstanceOf(SampleSource::class, SampleSource::Remote);
    }
}
