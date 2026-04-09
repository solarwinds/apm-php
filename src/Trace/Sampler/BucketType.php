<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

enum BucketType: string
{
    case DEFAULT = 'Default';
    case TRIGGER_RELAXED = 'TriggerRelaxed';
    case TRIGGER_STRICT = 'TriggerStrict';
}
