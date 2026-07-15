<?php

/**
 * Resolves asset meta data (dependencies + version) from a wp-scripts
 * `.asset.php` file or a Laravel Mix `mix-manifest.json` file.
 */

namespace Hybrid\Assets\Contracts;

trait AssetMetaData {
    /**
     * The manifest directory path.
     */
    protected ?string $manifestDirectory = null;

    /**
     * Manifest directory override for a single `resolve()` call.
     *
     * Set temporarily by `resolve()` and cleared immediately after, so it
     * never leaks into unrelated lookups.
     */
    protected ?string $manifestDirectoryOverride = null;

    /**
     * File name of the Mix manifest.
     */
    protected string $manifestFileName = 'mix-manifest.json';

    /**
     * Decoded contents of `mix-manifest.json`, cached after first load.
     *
     * @var array<string, string>|null
     */
    protected ?array $manifest = null;

    /**
     * Resolve meta data for an asset file.
     *
     * Tries a wp-scripts `.asset.php` file first, then falls back to the
     * Mix manifest.
     *
     * @param string $file Relative file path within the assets directory.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     *                                                                            Null when no meta data source has an entry for the file.
     */
    public function resolveAssetData( string $file ): ?array {
        return $this->readAssetPhpFile( $file ) ?? $this->readMixManifestEntry( $file );
    }

    /**
     * Read a `.asset.php` meta data file for the given asset, if one exists.
     *
     * @param string $file Relative file path within the assets directory.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     */
    protected function readAssetPhpFile( string $file ): ?array {
        $assetPhpPath = $this->assetPhpPath( $file );

        if ( ! is_file( $assetPhpPath ) ) {
            return null;
        }

        $data = include $assetPhpPath;

        if ( ! is_array( $data ) ) {
            return null;
        }

        return [
            'dependencies' => $data['dependencies'] ?? [],
            'version'      => $data['version'] ?? null,
        ];
    }

    /**
     * Resolve the absolute filesystem path to an asset's `.asset.php` file
     * (same file name, extension replaced with `.asset.php`).
     *
     * @param string $file Relative file path within the assets directory.
     */
    protected function assetPhpPath( string $file ): string {
        $extension            = pathinfo( $file, PATHINFO_EXTENSION );
        $fileWithoutExtension = $extension
            ? substr( $file, 0, -( strlen( $extension ) + 1 ) )
            : $file;

        return $this->path( $fileWithoutExtension . '.asset.php' );
    }

    /**
     * Look up a file's cache-busting version from `mix-manifest.json`.
     *
     * mix-manifest.json has no separate "version" field — it maps a source
     * path to a hashed output path (e.g. `/js/app.js?id=abc123` or
     * `/js/app.abc123.js`), and that hash is the cache buster. No
     * `dependencies` data exists in this format, so that key is always empty.
     *
     * @param string $file Relative file path within the assets directory.
     *
     * @return array{dependencies: array<int, string>, version: string|null}|null
     */
    protected function readMixManifestEntry( string $file ): ?array {
        $manifest = $this->loadManifest();

        if ( ! isset( $manifest[ $file ] ) ) {
            return null;
        }

        return [
            'dependencies' => [],
            'version'      => $this->extractMixVersion( $manifest[ $file ] ),
        ];
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
     * @return array<string, string> Empty array when the manifest doesn't exist.
     */
    protected function loadManifest(): array {
        if ( null !== $this->manifest ) {
            return $this->manifest;
        }

        $manifestPath = $this->getManifestPath();

        if ( ! is_file( $manifestPath ) ) {
            return [];
        }

        return $this->manifest = json_decode(
            (string) file_get_contents( $manifestPath ),
            true
        ) ?? [];
    }

    /**
     * Get the file path to the `mix-manifest.json` file.
     */
    protected function getManifestPath(): string {
        return $this->path( $this->prepareManifestDirectory() . '/' . $this->manifestFileName );
    }

    /**
     * Resolve the manifest directory, falling back: override → configured → assets directory.
     */
    protected function prepareManifestDirectory(): ?string {
        $manifestDirectory = $this->manifestDirectoryOverride
            ?: $this->manifestDirectory
                ?: $this->assetsDirectory;

        if ( $manifestDirectory && ! str_starts_with( $manifestDirectory, '/' ) ) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        return $manifestDirectory;
    }

    /**
     * Set the Mix manifest file name.
     *
     * @param string $manifestFileName Manifest file name.
     */
    public function setManifestFileName( string $manifestFileName ): static {
        $this->manifestFileName = $manifestFileName;

        return $this;
    }

    /**
     * Set the manifest directory path.
     *
     * @param string $manifestDirectory Manifest directory path.
     */
    public function setManifestDirectory( string $manifestDirectory ): static {
        $this->manifestDirectory = rtrim( $manifestDirectory, '/' );

        return $this;
    }
}
