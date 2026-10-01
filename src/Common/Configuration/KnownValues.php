<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Configuration;

interface KnownValues
{
    public const VALUE_SWOTRACESTATE = 'swotracestate';
    public const VALUE_XTRACEOPTIONS = 'xtraceoptions';
    public const VALUE_SWO = 'swo';
    public const VALUE_K8S = 'k8s';
    public const VALUE_UAMS = 'uams';
    public const VALUE_XTRACE = 'xtrace';
    public const VALUE_XTRACEOPTIONSRESPONSE = 'xtraceoptionsresponse';
    public const VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE = 'xtrace_options_response';
    public const VALUE_SAMPLER_SOLARWINDS_HTTP_DEFAULT_APM_COLLECTOR = 'apm.collector.na-01.cloud.solarwinds.com';
    public const VALUE_SAMPLER_SOLARWINDS_JSON_DEFAULT_PATH = '/tmp/solarwinds-apm-settings.json';
}
