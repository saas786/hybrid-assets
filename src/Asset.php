<?php

/**
 * Immutable, fully resolved asset — URL, path, dependencies, and version.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Concerns\AssetMetaData;
use Hybrid\Assets\Contracts\Asset as AssetContract;
use Hybrid\Assets\Contracts\AssetsResolver;

final class Asset implements AssetContract {

    use AssetMetaData;

    /**
     * @param \Hybrid\Assets\Contracts\AssetsResolver $assetResolver Theme or plugin this asset belongs to.
     * @param string                                  $file Relative file path within the assets directory.
     */
    public function __construct(
        protected AssetsResolver $assetResolver,
        protected string $file,
        protected string $manifestDirectory
    ) {}

    public function file(): string {
        return $this->file;
    }

    /**
     * Get the public URL of the asset.
     */
    public function url(): string {
        return $this->assetResolver->url( $this->file() );
    }

    /**
     * Get the absolute filesystem path of the asset.
     */
    public function path(): string {
        return $this->assetResolver->path( $this->file() );
    }

    /**
     * Get the asset's script/style dependencies.
     *
     * For WordPress-style assets (those with a `.asset.php` meta file), returns
     * the dependencies declared in that file. For Laravel Mix assets (using
     * `mix-manifest.json`), returns an empty array as Mix manifests do not
     * track dependency information.
     *
     * @param array<int, string> $additional Additional dependency handles to merge in.
     *
     * @return array<int, string> Handles of the asset's dependencies.
     */
    public function dependencies( array $additional = [] ): array {
        return array_values( array_unique( array_merge( $this->getMetaData()['dependencies'], $additional ) ) );
    }

    /**
     * Get the asset's cache-busting version string.
     *
     * Prefers a version from the resolved meta data (`.asset.php` or Mix
     * manifest); falls back to a content hash of the file itself when
     * neither source provides one.
     *
     * @return string|null Version string, or null if the file doesn't exist.
     */
    public function version(): ?string {
        return $this->getMetaData()['version'] ?? $this->getHash();
    }

    /**
     * Compute a short content hash for cache-busting when no manifest or
     * `.asset.php` version is available.
     *
     * @return string|null 20-character MD5 prefix, or null if the file doesn't exist.
     */
    private function getHash(): ?string {
        $absolutePath = $this->path();

        if ( ! is_file( $absolutePath ) || ! is_readable( $absolutePath ) ) {
            return null;
        }

        $hash = md5_file( $absolutePath );

        if ( false === $hash ) {
            return null;
        }

        return substr( $hash, 0, 20 );
    }
}
