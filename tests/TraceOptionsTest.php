<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\TraceOptions;

#[CoversClass(TraceOptions::class)]
class TraceOptionsTest extends TestCase
{
    public function test_no_key_no_value()
    {
        $header = '=';
        $result = TraceOptions::from($header);

        $this->assertEquals(new TraceOptions(), $result);
    }

    public function test_orphan_value()
    {
        $header = '=value';
        $result = TraceOptions::from($header);

        $this->assertEquals(new TraceOptions(), $result);
    }

    public function test_valid_trigger_trace()
    {
        $header = 'trigger-trace';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;

        $this->assertEquals($expected, $result);
    }

    public function test_trigger_trace_no_value()
    {
        $header = 'trigger-trace=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['trigger-trace', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_trigger_trace_duplicate()
    {
        $header = 'trigger-trace;trigger-trace';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->triggerTrace = true;
        $expected->ignored = [['trigger-trace', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_no_value()
    {
        $header = 'ts';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_duplicate()
    {
        $header = 'ts=1234;ts=5678';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->timestamp = 1234;
        $expected->ignored = [['ts', '5678']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_invalid()
    {
        $header = 'ts=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_float()
    {
        $header = 'ts=12.34';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['ts', '12.34']];

        $this->assertEquals($expected, $result);
    }

    public function test_timestamp_trim()
    {
        $header = 'ts = 1234567890 ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->timestamp = 1234567890;

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_no_value()
    {
        $header = 'sw-keys';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['sw-keys', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_duplicate()
    {
        $header = 'sw-keys=keys1;sw-keys=keys2';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'keys1';
        $expected->ignored = [['sw-keys', 'keys2']];

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_trim()
    {
        $header = 'sw-keys= name:value ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'name:value';

        $this->assertEquals($expected, $result);
    }

    public function test_sw_keys_ignore_after_semi()
    {
        $header = 'sw-keys=check-id:check-1013,website-id;booking-demo';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->swKeys = 'check-id:check-1013,website-id';
        $expected->ignored = [['booking-demo', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_trim()
    {
        $header = 'custom-key= value ';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'value'];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_no_value()
    {
        $header = 'custom-key';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['custom-key', null]];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_duplicate()
    {
        $header = 'custom-key=value1;custom-key=value2';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'value1'];
        $expected->ignored = [['custom-key', 'value2']];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_equals_in_value()
    {
        $header = 'custom-key=name=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->custom = ['custom-key' => 'name=value'];

        $this->assertEquals($expected, $result);
    }

    public function test_custom_keys_spaces_in_key()
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

    public function test_other_ignored()
    {
        $header = 'key=value';
        $result = TraceOptions::from($header);

        $expected = new TraceOptions();
        $expected->ignored = [['key', 'value']];

        $this->assertEquals($expected, $result);
    }

    public function test_trim_everything()
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

    public function test_semi_everywhere()
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

    public function test_single_quotes()
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

    public function test_missing_values_and_semi()
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
}
