<?php

/**
 * Contract for resolving asset paths and URLs.
 */

namespace Hybrid\Assets\Contracts;

interface AssetsInterface {
    /**
     * Get the absolute filesystem path of a file.
     *
     * @param string $file Relative file path.
     *
     * @return string Absolute filesystem path.
     */
    public function path( string $file ): string;

    /**
     * Get the public URL of a file.
     *
     * @param string $file Relative file path.
     *
     * @return string Public URL of the file.
     */
    public function url( string $file ): string;
}
