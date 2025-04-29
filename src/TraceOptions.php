<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

const TRIGGER_TRACE_KEY = 'trigger-trace';
const TIMESTAMP_KEY = 'ts';
const SW_KEYS_KEY = 'sw-keys';
const CUSTOM_KEY_REGEX = '/^custom-[^\s]+$/';

class TraceOptions
{
    public ?bool $triggerTrace = null;
    public ?int $timestamp = null;
    public ?string $swKeys = null;
    public array $custom = [];
    public array $ignored = [];

    public function __construct(
        ?bool $triggerTrace = null,
        ?int $timestamp = null,
        ?string $swKeys = null,
        array $custom = [],
        array $ignored = [],
    ) {
        $this->triggerTrace = $triggerTrace;
        $this->timestamp = $timestamp;
        $this->swKeys = $swKeys;
        $this->custom = $custom;
        $this->ignored = $ignored;
    }

    public static function from(string $header): TraceOptions
    {
        $traceOptions = new TraceOptions();
        $kvs = array_filter(array_map(function ($kv) {
            $parts = array_map('trim', explode('=', $kv, 2));

            return count($parts) === 2 ? $parts : [$parts[0], null];
        }, explode(';', $header)), function ($kv) {
            return strlen($kv[0]) > 0;
        });
        foreach ($kvs as [$k, $v]) {
            if ($k === TRIGGER_TRACE_KEY) {
                if ($v !== null || $traceOptions->triggerTrace !== null) {
                    // error_log('invalid trace option for trigger trace, should not have a value and only be provided once');
                    $traceOptions->ignored[] = [$k, $v];

                    continue;
                }
                $traceOptions->triggerTrace = true;
            } elseif ($k === TIMESTAMP_KEY) {
                if ($v === null || $traceOptions->timestamp !== null) {
                    // error_log('invalid trace option for timestamp, should have a value and only be provided once');
                    $traceOptions->ignored[] = [$k, $v];

                    continue;
                }
                if (!is_numeric($v) || str_contains($v, '.')) {
                    // error_log('invalid trace option for timestamp, should be an integer');
                    $traceOptions->ignored[] = [$k, $v];

                    continue;
                }
                $traceOptions->timestamp = (int) $v;
            } elseif ($k === SW_KEYS_KEY) {
                if ($v === null || $traceOptions->swKeys !== null) {
                    // error_log('invalid trace option for sw keys, should have a value and only be provided once');
                    $traceOptions->ignored[] = [$k, $v];

                    continue;
                }
                $traceOptions->swKeys = $v;
            } elseif (preg_match(CUSTOM_KEY_REGEX, $k)) {
                if ($v === null || array_key_exists($k, $traceOptions->custom)) {
                    // error_log("invalid trace option for custom key $k, should have a value and only be provided once");
                    $traceOptions->ignored[] = [$k, $v];

                    continue;
                }
                $traceOptions->custom[$k] = $v;
            } else {
                $traceOptions->ignored[] = [$k, $v];
            }
        }

        return $traceOptions;
    }

    public function __toString()
    {
        $kvs = [
            TRIGGER_TRACE_KEY => $this->triggerTrace ? 'true' : null,
            TIMESTAMP_KEY => $this->timestamp !== null ? (string) ($this->timestamp) : null,
            SW_KEYS_KEY => $this->swKeys,
            'custom' => implode(';', array_map(function ($k, $v) {
                return "$k=$v";
            }, array_keys($this->custom), $this->custom)),
            'ignored' => implode(';', array_map(function ($k, $v) {
                if (is_array($v) && count($v) === 2) {
                    return "$v[0]=$v[1]";
                }

                return "$k=$v";

            }, array_keys($this->ignored), $this->ignored)),
        ];

        return implode(',', array_filter(array_map(function ($k, $v) {
            return $v !== null ? "$k=$v" : '';
        }, array_keys($kvs), $kvs)));
    }
}
