<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

enum Auth: string
{
    case OK = 'ok';
    case BAD_TIMESTAMP = 'bad-timestamp';
    case BAD_SIGNATURE = 'bad-signature';
    case NO_SIGNATURE_KEY = 'no-signature-key';
}