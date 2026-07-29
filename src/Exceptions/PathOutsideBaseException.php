<?php

namespace Hybrid\Assets\Exceptions;

use Hybrid\Assets\Contracts\AssetsException;

final class PathOutsideBaseException extends \InvalidArgumentException implements AssetsException {
    public static function forPath( string $resolvedPath, string $baseDirectory ): self {
        return new self( sprintf(
            'Path "%s" is not within the resolver base "%s".',
            $resolvedPath,
            $baseDirectory
        ) );
    }
}
