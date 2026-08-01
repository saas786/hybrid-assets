<?php

namespace Hybrid\Assets\Exceptions;

use Hybrid\Assets\Contracts\AssetsException;

/**
 * Thrown when an `AssetsResolver`'s base directory cannot be resolved via
 * `realpath()` (missing, unreadable, or otherwise invalid).
 *
 * This guards a specific failure mode: `realpath()` returns `false` on
 * failure, and comparing a resolved file path against a `false` base with
 * `str_starts_with()` would coerce it to an empty string, making the
 * containment check pass unconditionally instead of failing. Throwing here
 * keeps the check fail-closed rather than silently permissive.
 */
final class UnresolvableBaseDirectoryException extends \RuntimeException implements AssetsException {
    public static function forPath( string $basePath ): self {
        return new self( sprintf(
            'Resolver base directory "%s" could not be resolved to a real path.',
            $basePath
        ) );
    }
}
