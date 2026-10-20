<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Configuration;

interface KnownValues
{
    public const VALUE_TRACESTATE = 'swo_tracestate';
    public const VALUE_XTRACEOPTIONS = 'swo_xtraceoptions';
    public const VALUE_APM = 'swo_apm';
    public const VALUE_K8S = 'swo_k8s';
    public const VALUE_UAMS = 'swo_uams';
    public const VALUE_XTRACE = 'swo_xtrace';
    public const VALUE_XTRACEOPTIONSRESPONSE = 'swo_xtraceoptionsresponse';
    public const VALUE_TRACESTATE_XTRACE_OPTIONS_RESPONSE = 'xtrace_options_response';
    public const VALUE_SAMPLER_SWO_HTTP_DEFAULT_APM_COLLECTOR = 'apm.collector.na-01.cloud.solarwinds.com';
    public const VALUE_SAMPLER_SWO_JSON_DEFAULT_PATH = '/tmp/solarwinds-apm-settings.json';
}
