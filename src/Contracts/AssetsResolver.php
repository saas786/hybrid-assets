<?php

/**
 * Contract for resolving asset paths and URLs.
 */

namespace Hybrid\Assets\Contracts;

interface AssetsResolver {
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

    /**
     * Get the asset URL for a file.
     *
     * @param string $file Relative file path within the assets directory.
     * @param bool   $inherit Check child theme first, where applicable.
     */
    public function assetUrl( string $file, bool $inherit = false ): string;

    /**
     * Get the absolute filesystem path for a file within the assets directory.
     *
     * @param string $file Relative file path within the assets directory.
     * @param bool   $inherit Check child theme first, where applicable.
     */
    public function assetPath( string $file, bool $inherit = false ): string;

    /**
     * Resolve a fully-described `Asset` instance for a file.
     *
     * @param string $file Relative file path, e.g. `js/admin/tabs.js`.
     * @param bool   $inherit Whether to check the inheritance chain (e.g. child theme) first.
     * @param string $manifestDirectory Optional per-lookup manifest directory override.
     */
    public function asset( string $file, bool $inherit = false, string $manifestDirectory = '' ): Asset;

    /**
     * Get the assets directory path.
     */
    public function getAssetsDirectory(): string;

    /**
     * Get the manifest directory path, if one was configured.
     */
    public function getManifestDirectory(): string;

    /**
     * Get the Mix manifest file name.
     */
    public function getManifestFileName(): string;
}
