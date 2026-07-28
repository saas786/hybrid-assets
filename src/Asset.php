<?php

/**
 * Immutable, fully resolved asset — URL, path, dependencies, and version.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Contracts\AssetsAbstract;

final class Asset {
    /**
     * Cached meta data (dependencies + version), loaded on first access.
     *
     * @var array{dependencies: array<int, string>, version: string|null}|null
     */
    private ?array $metaData = null;

    /**
     * @param \Hybrid\Assets\Contracts\AssetsAbstract $assetResolver Theme or plugin this asset belongs to.
     * @param string                                  $file Relative file path within the assets directory.
     */
    public function __construct(
        protected AssetsAbstract $assetResolver,
        protected string $file
    ) {}

    /**
     * Get the public URL of the asset.
     */
    public function url(): string {
        return $this->assetResolver->url( $this->file );
    }

    /**
     * Get the absolute filesystem path of the asset.
     */
    public function path(): string {
        return $this->assetResolver->path( $this->file );
    }

    /**
     * Get the asset's script/style dependencies, as declared in its
     * `.asset.php` meta data file. Always empty for Mix-built assets, since
     * `mix-manifest.json` doesn't record dependencies.
     *
     * @return array<int, string> Handles of the asset's dependencies.
     */
    public function dependencies(): array {
        return $this->getMetaData()['dependencies'];
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
     * Resolve and memoize this asset's meta data.
     *
     * @return array{dependencies: array<int, string>, version: string|null}
     */
    private function getMetaData(): array {
        return $this->metaData ??= $this->assetResolver->resolveAssetData( $this->file ) ?? [
            'dependencies' => [],
            'version'      => null,
        ];
    }

    /**
     * Compute a short content hash for cache-busting when no manifest or
     * `.asset.php` version is available.
     *
     * @return string|null 20-character MD5 prefix, or null if the file doesn't exist.
     */
    private function getHash(): ?string {
        $absolutePath = $this->path( $this->file );

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
