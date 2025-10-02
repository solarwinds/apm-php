<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\TraceOptions;

#[CoversClass(TraceOptions::class)]
class TraceOptionsTest extends TestCase
{
    public function test_no_key_no_value(): void
    {
        $header = '=';
        $result = TraceOptions::from($header);

        $this->assertEquals(new TraceOptions(), $result);
    }

    public function test_orphan_value(): void
    {
        $header = '=value';
        $result = TraceOptions::from($header);

        $this->assertEquals(new TraceOptions(), $result);
    }

    public function test_valid_trigger_trace(): void
    {
        $header = 'trigger-trace';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;

        $this->assertEquals($expected, $result);
    }

    public function test_trigger_trace_no_value(): void
    {
        $header = 'trigger-trace=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['trigger-trace', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_trigger_trace_duplicate(): void
    {
        $header = 'trigger-trace;trigger-trace';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->ignored = [['trigger-trace', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_no_value(): void
    {
        $header = 'ts';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_duplicate(): void
    {
        $header = 'ts=1234;ts=5678';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->timestamp = 1234;
        $expected->ignored = [['ts', '5678']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_invalid(): void
    {
        $header = 'ts=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_float(): void
    {
        $header = 'ts=12.34';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', '12.34']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_trim(): void
    {
        $header = 'ts = 1234567890 ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->timestamp = 1234567890;

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_no_value(): void
    {
        $header = 'sw-keys';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['sw-keys', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_duplicate(): void
    {
        $header = 'sw-keys=keys1;sw-keys=keys2';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'keys1';
        $expected->ignored = [['sw-keys', 'keys2']];

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_trim(): void
    {
        $header = 'sw-keys= name:value ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'name:value';

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_ignore_after_semi(): void
    {
        $header = 'sw-keys=check-id:check-1013,website-id;booking-demo';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'check-id:check-1013,website-id';
        $expected->ignored = [['booking-demo', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_trim(): void
    {
        $header = 'custom-key= value ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'value'];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_no_value(): void
    {
        $header = 'custom-key';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['custom-key', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_duplicate(): void
    {
        $header = 'custom-key=value1;custom-key=value2';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'value1'];
        $expected->ignored = [['custom-key', 'value2']];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_equals_in_value(): void
    {
        $header = 'custom-key=name=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'name=value'];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_spaces_in_key(): void
    {
        $header = 'custom- key=value;custom-ke y=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [
            ['custom- key', 'value'],
            ['custom-ke y', 'value'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_other_ignored(): void
    {
        $header = 'key=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['key', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_trim_everything(): void
    {
        $header = 'trigger-trace ; custom-something=value; custom-OtherThing = other val ; sw-keys = 029734wr70:9wqj21,0d9j1 ; ts = 12345 ; foo = bar';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->swKeys = '029734wr70:9wqj21,0d9j1';
        $expected->timestamp = 12345;
        $expected->custom = [
            'custom-something' => 'value',
            'custom-OtherThing' => 'other val',
        ];
        $expected->ignored = [['foo', 'bar']];

        $this->assertEquals($expected, $result);
    }

    public function test_semi_everywhere(): void
    {
        $header = ';foo=bar;;;custom-something=value_thing;;sw-keys=02973r70:1b2a3;;;;custom-key=val;ts=12345;;;;;;;trigger-trace;;;';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->swKeys = '02973r70:1b2a3';
        $expected->timestamp = 12345;
        $expected->custom = [
            'custom-something' => 'value_thing',
            'custom-key' => 'val',
        ];
        $expected->ignored = [['foo', 'bar']];

        $this->assertEquals($expected, $result);
    }

    public function test_single_quotes(): void
    {
        $header = "trigger-trace;custom-foo='bar;bar';custom-bar=foo";
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->custom = [
            'custom-foo' => "'bar",
            'custom-bar' => 'foo',
        ];
        $expected->ignored = [["bar'", null]];

        $this->assertEquals($expected, $result);
    }

    public function test_missing_values_and_semi(): void
    {
        $header = ';trigger-trace;custom-something=value_thing;sw-keys=02973r70:9wqj21,0d9j1;1;2;3;4;5;=custom-key=val?;=';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->swKeys = '02973r70:9wqj21,0d9j1';
        $expected->custom = [
            'custom-something' => 'value_thing',
        ];
        $expected->ignored = [
            ['1', null],
            ['2', null],
            ['3', null],
            ['4', null],
            ['5', null],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_constructor_assigns_properties_correctly(): void
    {
        $obj = new TraceOptions(true, 123, 'sw', ['custom-key' => 'val'], [['ignored', 'x']]);
        $this->assertTrue($obj->triggerTrace);
        $this->assertSame(123, $obj->timestamp);
        $this->assertSame('sw', $obj->swKeys);
        $this->assertSame(['custom-key' => 'val'], $obj->custom);
        $this->assertSame([['ignored', 'x']], $obj->ignored);
    }

    public function test_constructor_defaults(): void
    {
        $obj = new TraceOptions();
        $this->assertNull($obj->triggerTrace);
        $this->assertNull($obj->timestamp);
        $this->assertNull($obj->swKeys);
        $this->assertSame([], $obj->custom);
        $this->assertSame([], $obj->ignored);
    }

    public function test_to_string_all_unset(): void
    {
        $obj = new TraceOptions();
        $str = (string) $obj;
        $this->assertStringNotContainsString('trigger-trace=true', $str);
        $this->assertStringNotContainsString('ts=42', $str);
        $this->assertStringNotContainsString('sw-keys=sw', $str);
        $this->assertStringContainsString('custom=', $str);
        $this->assertStringContainsString('ignored=', $str);
    }

    public function test_to_string_all_set(): void
    {
        $obj = new TraceOptions(true, 42, 'sw', ['custom-key' => 'val'], [['foo', 'bar']]);
        $str = (string) $obj;
        $this->assertStringContainsString('trigger-trace=true', $str);
        $this->assertStringContainsString('ts=42', $str);
        $this->assertStringContainsString('sw-keys=sw', $str);
        $this->assertStringContainsString('custom=custom-key=val', $str);
        $this->assertStringContainsString('ignored=foo=bar', $str);
    }

    public function test_to_string_custom_and_ignored(): void
    {
        $obj = new TraceOptions(null, null, null, ['custom1' => 'v1', 'custom2' => 'v2'], [['a', 'b'], 'str']);
        $str = (string) $obj;
        $this->assertStringContainsString('custom=custom1=v1;custom2=v2', $str);
        $this->assertStringContainsString('ignored=a=b;str', $str);
    }

    public function test_to_string_each_property(): void
    {
        $obj = new TraceOptions(true);
        $this->assertStringContainsString('trigger-trace=true', (string) $obj);
        $obj = new TraceOptions(null, 99);
        $this->assertStringContainsString('ts=99', (string) $obj);
        $obj = new TraceOptions(null, null, 'swk');
        $this->assertStringContainsString('sw-keys=swk', (string) $obj);
        $obj = new TraceOptions(null, null, null, ['c' => 'v']);
        $this->assertStringContainsString('custom=c=v', (string) $obj);
        $obj = new TraceOptions(null, null, null, [], [['x', 'y']]);
        $this->assertStringContainsString('ignored=x=y', (string) $obj);
    }
}
