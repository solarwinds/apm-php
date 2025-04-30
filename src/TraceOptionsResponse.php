<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class TraceOptionsResponse
{
    public ?Auth $auth = null;
    public ?TriggerTrace $triggerTrace = null;
    public ?array $ignored = null;

    public function __toString()
    {
        $kvs = [
            'auth' => $this->auth?->value,
            'trigger-trace' => $this->triggerTrace?->value,
            'ignored' => $this->ignored !== null ? implode(',', $this->ignored) : null,
        ];

        return implode(';', array_filter(array_map(function ($k, $v) {
            return $v !== null ? "$k=$v" : '';
        }, array_keys($kvs), $kvs)));
    }
}
