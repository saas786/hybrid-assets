<?php

namespace Hybrid\Assets\Exceptions;

use Hybrid\Assets\Contracts\AssetsException;

final class InvalidAssetFileException extends \InvalidArgumentException implements AssetsException {
    public static function blank(): self {
        return new self( 'Asset file path must not be blank.' );
    }
}
