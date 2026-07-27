<?php

/**
 * Base implementation of AssetsInterface — manifest resolution, versioning, path prep.
 */

namespace Hybrid\Assets\Contracts;

use Hybrid\Assets\Asset;
use Hybrid\Assets\Plugin;
use function Hybrid\app;

abstract class AssetsAbstract implements AssetsInterface {

    use AssetMetaData;

    /**
     * Assets directory path.
     */
    protected string $assetsDirectory = '/public';

    /**
     * Get the asset URL for a file.
     *
     * @param string $file Relative file path within the assets directory.
     * @param bool   $inherit Check child theme first, where applicable.
     */
    public function assetUrl( string $file, bool $inherit = false ): string {
        return $this->asset( $file, $inherit )->url();
    }

    /**
     * Get the absolute filesystem path for a file within the assets directory.
     *
     * @param string $file Relative file path within the assets directory.
     * @param bool   $inherit Check child theme first, where applicable.
     */
    public function assetPath( string $file, bool $inherit = false ): string {
        return $this->asset( $file, $inherit )->path();
    }

    /**
     * Resolve a fully-described `Asset` instance for a file.
     *
     * @param string $file Relative file path, e.g. `js/admin/tabs.js`.
     * @param bool   $inherit Whether to check the inheritance chain (e.g. child theme) first.
     */
    public function asset( string $file, bool $inherit = false, string $manifestDirectory = '' ): Asset {
        $file = $this->normalizeFile( $file );

        if ( $inherit ) {
            // Plugins are looked up under their override directory in the theme(s);
            // themes are looked up under their own assets directory as-is.
            $lookupFile = $this instanceof Plugin
                ? $this->prependOverrideAssetsDirectory( $file )
                : $this->prependAssetsDir( $file );

            foreach ( $this->inheritanceChain() as $resolver ) {
                if ( $resolver->exists() && is_readable( $resolver->path( $lookupFile ) ) ) {
                    return $resolver->resolve( $lookupFile, $manifestDirectory );
                }
            }
        }

        return $this->resolve( $this->prependAssetsDir( $file ), $manifestDirectory );
    }

    /**
     * Prepend this resolver's override assets directory to a file path, for use
     * when looking the file up in *other* resolvers (the inheritance chain).
     */
    protected function prependOverrideAssetsDirectory( string $file ): string {
        $overrideAssetsDirectory = trim( $this->overrideAssetsDirectory(), '/' );

        return '' === $overrideAssetsDirectory ? $file : $overrideAssetsDirectory . $file;
    }

    /**
     * Directory name used by other resolvers when overriding this resolver's assets
     * (currently only meaningful for `Plugin`). When non-empty, inheritance
     * lookups prefix the requested file with this value, so a theme can host
     * overrides for several plugins side by side, e.g.
     * `{theme}/public/{override-assets-directory}/js/tabs.js`.
     *
     * Only applies when *this* resolver looks outward into its inheritance
     * chain — resolving a file against this resolver's own files never uses it.
     */
    public function overrideAssetsDirectory(): string {
        return '';
    }

    /**
     * Resolve this resolver's inheritance chain to actual `AssetsAbstract` instances,
     * silently skipping any binding that isn't registered or fails to resolve.
     *
     * @return array<int, \Hybrid\Assets\Contracts\AssetsAbstract>
     */
    protected function inheritanceChain(): array {
        $app   = app();
        $chain = [];

        foreach ( $this->inheritance as $binding ) {
            if ( ! $app || ! $app->bound( $binding ) ) {
                continue;
            }

            try {
                $resolved = $app->make( $binding );
            } catch ( \Throwable ) {
                // Skip bindings that fail to resolve rather than breaking asset lookup.
                continue;
            }

            if ( $resolved instanceof self ) {
                $chain[] = $resolved;
            }
        }

        return $chain;
    }

    /**
     * Resolve an `Asset` against this resolver specifically, without consulting
     * the inheritance chain. Metadata is looked up via `.asset.php`, then
     * the Mix manifest, then a filemtime-based hash fallback.
     *
     * @param string $file Relative file path to look up, e.g. `public/js/tabs.js`.
     */
    public function resolve( string $file, string $manifestDirectory = '' ): Asset {
        if ( $manifestDirectory ) {
            $this->manifestDirectoryOverride = $manifestDirectory;
        }

        $asset = new Asset(
            assetResolver: $this,
            file: $file,
            absolutePath: $this->path( $file )
        );

        $this->manifestDirectoryOverride = null;

        return $asset;
    }

    /**
     * Prepend the assets directory to a file path.
     *
     * @param string $file Relative file path within the assets.
     */
    protected function prependAssetsDir( string $file ): string {
        return $this->assetsDirectory . $file;
    }

    /**
     * Ensure a file path starts with a leading slash.
     *
     * @param string $file Relative file path.
     */
    protected function normalizeFile( string $file ): string {
        return str_starts_with( $file, '/' ) ? $file : "/{$file}";
    }

    /**
     * Set the assets directory path.
     *
     * @param string $assetsDirectory Assets directory path.
     */
    public function setAssetsDirectory( string $assetsDirectory ): void {
        $this->assetsDirectory = rtrim( $assetsDirectory, '/' );
    }

    /**
     * Get the assets directory path.
     */
    public function getAssetsDirectory(): ?string {
        return $this->assetsDirectory;
    }
}
