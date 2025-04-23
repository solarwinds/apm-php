<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

enum BucketType: string
{
    case DEFAULT = '';
    case TRIGGER_RELAXED = 'TriggerRelaxed';
    case TRIGGER_STRICT = 'TriggerStrict';
}
