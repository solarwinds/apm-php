<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

enum TriggerTrace: string
{
    case OK = 'ok';
    case NOT_REQUESTED = 'not-requested';
    case IGNORED = 'ignored';
    case TRACING_DISABLED = 'tracing-disabled';
    case TRIGGER_TRACING_DISABLED = 'trigger-tracing-disabled';
    case RATE_EXCEEDED = 'rate-exceeded';
    case SETTINGS_NOT_AVAILABLE = 'settings-not-available';
}