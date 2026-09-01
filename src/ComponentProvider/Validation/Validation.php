<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\ComponentProvider\Validation;

use Closure;
use InvalidArgumentException;
use function is_string;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * Provides validation closures for {@see NodeDefinition}s.
 *
 * @see NodeDefinition::validate()
 */
final class Validation
{
    public static function ensureEnabledDisabled(): Closure
    {
        return static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException('must be of type string');
            }
            if (!in_array($value, ['enabled', 'disabled'], true)) {
                throw new InvalidArgumentException('must be either "enabled" or "disabled"');
            }
            return $value;
        };
    }

    public static function ensureServiceKey(): Closure
    {
        return static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException('must be of type string');
            }

            if (!preg_match('/^([^:]+):([^:]+)$/', $value)) {
                throw new InvalidArgumentException('must match the service key pattern');
            }
            return $value;
        };
    }
}
