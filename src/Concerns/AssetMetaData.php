<?php

/**
 * Resolves asset meta data (dependencies + version) from a wp-scripts
 * `.asset.php` file or a Laravel Mix `mix-manifest.json` file.
 *
 * Consuming classes must provide:
 *  - `file(): string` — the resolved, relative asset path.
 *  - `$assetResolver` — the `AssetsResolver` the asset was resolved against.
 *  - `$manifestDirectory` — a per-asset manifest directory override (may be empty).
 */

namespace Hybrid\Assets\Concerns;

use Hybrid\Assets\Exceptions\PathOutsideBaseException;
use Hybrid\Assets\Exceptions\UnresolvableBaseDirectoryException;

trait AssetMetaData {
    /**
     * Decoded contents of `mix-manifest.json`, cached after first load.
     * `null` until a load has been attempted.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $manifest = null;

    /**
     * Cached meta data (dependencies + version), loaded on first access.
     *
     * @var array{dependencies: array<int, string>, version: string|null}|null
     */
    private ?array $metaData = null;

    /**
     * Resolve and memoize this asset's meta data.
     *
     * @return array{dependencies: array<int, string>, version: string|null}
     */
    private function getMetaData(): array {
        return $this->metaData ??= $this->resolveAssetData() ?? [
            'dependencies' => [],
            'version'      => null,
        ];
    }

    /**
     * Resolve meta data for an asset file.
     *
     * Tries a wp-scripts `.asset.php` file first, then falls back to the
     * Mix manifest.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     *                                                                            Null when no meta data source has an entry for the file.
     */
    public function resolveAssetData(): ?array {
        return $this->readAssetPhpFile() ?? $this->readMixManifestEntry();
    }

    /**
     * Read a `.asset.php` meta data file for the given asset, if one exists.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     */
    protected function readAssetPhpFile(): ?array {
        $assetPhpPath = $this->assetPhpPath();

        if ( is_null( $assetPhpPath ) ) {
            return null;
        }

        // Only ever include a `.asset.php` meta file — never the asset itself,
        // and never anything else that happens to sit at the resolved path.
        if ( ! str_ends_with( $assetPhpPath, '.asset.php' ) || ! is_file( $assetPhpPath ) ) {
            return null;
        }

        $data = include $assetPhpPath;

        if ( ! is_array( $data ) ) {
            return null;
        }

        $dependencies = $data['dependencies'] ?? [];
        $version      = $data['version'] ?? null;

        return [
            'dependencies' => is_array( $dependencies ) ? array_values( $dependencies ) : [],
            'version'      => is_scalar( $version ) ? (string) $version : null,
        ];
    }

    /**
     * Resolve the absolute filesystem path to an asset's `.asset.php` file
     * (same file name, extension replaced with `.asset.php`).
     */
    protected function assetPhpPath(): ?string {
        $file      = $this->file();
        $extension = pathinfo( $file, PATHINFO_EXTENSION );

        if ( '' !== $extension ) {
            $file = substr( $file, 0, -( strlen( $extension ) + 1 ) );
        }

        $assetFilePath = realpath( $this->assetResolver->path( $file . '.asset.php' ) );

        // Missing, unreadable, or a directory — no meta data, but not an error.
        if ( false === $assetFilePath || ! is_file( $assetFilePath ) ) {
            return null;
        }

        $basePath          = $this->assetResolver->path();
        $realBaseDirectory = realpath( $basePath );

        // `realpath()` returns `false` when the base directory can't be
        // resolved (missing, unreadable, etc.). Treating that as "no
        // restriction" would let `str_starts_with()` coerce it to an empty
        // string and pass every path — silently defeating the containment
        // check below. Fail closed instead.
        if ( false === $realBaseDirectory ) {
            throw UnresolvableBaseDirectoryException::forPath( $basePath );
        }

        // The trailing separator anchors the match to a directory boundary, so
        // a sibling like `/site-evil` can't pass as being inside `/site`.
        if ( ! str_starts_with( $assetFilePath, $realBaseDirectory . \DIRECTORY_SEPARATOR ) ) {
            throw PathOutsideBaseException::forPath( $assetFilePath, $realBaseDirectory );
        }

        return $assetFilePath;
    }

    /**
     * Look up a file's cache-busting version from `mix-manifest.json`.
     *
     * mix-manifest.json has no separate "version" field — it maps a source
     * path to a hashed output path (e.g. `/js/app.js?id=abc123` or
     * `/js/app.abc123.js`), and that hash is the cache buster. No
     * `dependencies` data exists in this format, so that key is always empty.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     */
    protected function readMixManifestEntry(): ?array {
        $manifest = $this->loadManifest();

        foreach ( $this->manifestKeys() as $key ) {
            if ( ! isset( $manifest[ $key ] ) || ! is_string( $manifest[ $key ] ) ) {
                continue;
            }

            return [
                'dependencies' => [],
                'version'      => $this->extractMixVersion( $manifest[ $key ] ),
            ];
        }

        return null;
    }

    /**
     * Candidate keys to look this asset up under in the Mix manifest.
     *
     * Mix keys entries relative to its own output (manifest) directory —
     * `/js/app.js`, not `/public/js/app.js` — so the manifest directory is
     * stripped first. The full resolved path is kept as a fallback for
     * manifests that were generated with it included.
     *
     * @return array<int, string>
     */
    protected function manifestKeys(): array {
        $file              = $this->file();
        $manifestDirectory = $this->prepareManifestDirectory();
        $keys              = [];

        if ( '' !== $manifestDirectory && str_starts_with( $file, "{$manifestDirectory}/" ) ) {
            $keys[] = substr( $file, strlen( $manifestDirectory ) );
        }

        $keys[] = $file;

        return $keys;
    }

    /**
     * Extract the version hash from a `mix-manifest.json` output path.
     *
     * Mix hashes assets one of two ways depending on configuration:
     *  - Query string:  `/js/app.js?id=abc123def456`
     *  - Filename hash: `/js/app.abc123de.js`
     *
     * @param string $hashedPath The manifest's output path for the file.
     */
    protected function extractMixVersion( string $hashedPath ): ?string {
        if ( str_contains( $hashedPath, '?id=' ) ) {
            return substr( $hashedPath, strpos( $hashedPath, '?id=' ) + 4 );
        }

        // Filename hash form: `name.{hash}.ext` — the hash is the last
        // dot-separated segment of the file name.
        $segments = explode( '.', pathinfo( $hashedPath, PATHINFO_FILENAME ) );

        return count( $segments ) > 1 ? end( $segments ) : null;
    }

    /**
     * Load and cache the `mix-manifest.json` contents.
     *
     * @return array<string, mixed> Empty array when the manifest is missing or invalid.
     */
    protected function loadManifest(): array {
        if ( null !== $this->manifest ) {
            return $this->manifest;
        }

        $manifestPath = $this->getManifestPath();

        if ( ! is_file( $manifestPath ) || ! is_readable( $manifestPath ) ) {
            return $this->manifest = [];
        }

        $decoded = json_decode( (string) file_get_contents( $manifestPath ), true );

        return $this->manifest = is_array( $decoded ) ? $decoded : [];
    }

    /**
     * Get the file path to the `mix-manifest.json` file.
     */
    protected function getManifestPath(): string {
        return $this->assetResolver->path(
            $this->prepareManifestDirectory() . '/' . $this->assetResolver->getManifestFileName()
        );
    }

    /**
     * Resolve the manifest directory, falling back: override → configured → assets directory.
     * Always returned with a leading slash, or as an empty string.
     */
    protected function prepareManifestDirectory(): string {
        $manifestDirectory = $this->getManifestDirectory()
            ?: $this->assetResolver->getManifestDirectory()
                ?: $this->assetResolver->getAssetsDirectory();

        $manifestDirectory = trim( (string) $manifestDirectory, '/' );

        return '' === $manifestDirectory ? '' : "/{$manifestDirectory}";
    }

    /**
     * Get the manifest directory path, per Asset optional.
     */
    protected function getManifestDirectory(): string {
        return $this->manifestDirectory;
    }
}
