<?php
/**
 * Interface for handling assets - path and URL.
 */

namespace Hybrid\Assets\Contracts;

/**
 * Interface AssetsInterface
 * Describes methods for handling assets - path and URL.
 */
interface AssetsInterface {

    /**
     * Get the absolute filesystem path of a file.
     *
     * @param string $file File path within the assets.
     * @return string Absolute filesystem path of the file.
     */
    public function path( string $file ): string;

    /**
     * Get the URL for a file.
     *
     * @param string $file File path within the assets.
     * @return string URL of the file.
     */
    public function url( string $file ): string;

}
