<?php

namespace Hybrid\Assets\Exceptions;

use Hybrid\Assets\Contracts\AssetsException;

/**
 * Thrown by `Plugin::pluginFile()` when `setPluginFile()` was never called.
 *
 * Extends `\LogicException` (a programmer-error / misconfiguration signals
 * it, not runtime input) while also implementing `AssetsException`, so
 * callers catching this package's exception contract catch it too.
 */
final class PluginFileNotSetException extends \LogicException implements AssetsException {
    public static function forResolver( string $resolverClass ): self {
        return new self(
            "No plugin file set on {$resolverClass}. Call setPluginFile( __FILE__ ) " .
            "with the plugin's main file when binding the resolver into the container."
        );
    }
}
