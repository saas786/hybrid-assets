<?php
/**
 * Abstract class implementing AssetsInterface with common functionality for handling assets.
 */

namespace Hybrid\Assets\Contracts;

/**
 * Class AssetsAbstract
 * Abstract class implementing AssetsInterface with common functionality for handling assets.
 */
abstract class AssetsAbstract implements AssetsInterface {

    /**
     * Assets directory path.
     */
    protected string $assetsDirectory = '/public';

    /**
     * The manifest directory path.
     */
    protected ?string $manifestDirectory = null;

    /**
     * The custom manifest directory path which can be provided directly to the assetUrl() and assertPath() methods.
     */
    protected ?string $customManifestDirectory = null;

    /**
     * Name of the manifest file.
     */
    protected string $manifestName = 'mix-manifest.json';

    /**
     * The loaded manifest array.
     *
     * @var array|null
     */
    protected ?array $manifest = null;

    /**
     * Get the asset URL for a file.
     *
     * This method constructs the URL for a given asset file using the provided file path and manifest directory.
     * If a custom manifest directory is provided, it's used temporarily for this request.
     *
     * @param string $file The file path within the assets directory.
     * @param string $manifestDirectory (optional) Custom manifest directory path.
     * @return string URL of the asset file.
     */
    public function assetUrl( string $file, string $manifestDirectory = '' ): string {
        // Set a custom manifest path for this request only, if provided.
        if ( $manifestDirectory ) {
            $this->customManifestDirectory = $manifestDirectory;
        }

        // Get the URL of the prepared asset.
        $url = $this->url( $this->prepareAsset( $file ) );

        // Reset custom manifest path for subsequent requests, to avoid unintended behavior.
        $this->customManifestDirectory = null;

        return $url;
    }

    /**
     * Get the absolute filesystem path for a file within the assets directory.
     *
     * This method constructs the absolute filesystem path for a given asset file using the provided file path
     * and manifest directory. If a custom manifest directory is provided, it's used temporarily for this request.
     *
     * @param string $file The file path within the assets directory.
     * @param string $manifestDirectory (optional) Custom manifest directory path.
     * @return string Absolute filesystem path of the asset file.
     */
    public function assetPath( string $file, string $manifestDirectory = '' ): string {
        // Set a custom manifest path for this request only, if provided.
        if ( $manifestDirectory ) {
            $this->customManifestDirectory = $manifestDirectory;
        }

        // Get the absolute filesystem path of the prepared asset.
        $path = $this->path( $this->prepareAsset( $file ) );

        // Reset custom manifest path for subsequent requests, to avoid unintended behavior.
        $this->customManifestDirectory = null;

        return $path;
    }

    /**
     * Prepare an asset file for usage, considering the manifest.
     *
     * @param string $file File path within the assets.
     * @return string Prepared asset file path.
     */
    public function prepareAsset( $file ) {
        if ( ! str_starts_with( $file, '/' ) ) {
            $file = "/{$file}";
        }

        $manifest = $this->getManifest();

        // Gets the path from the manifest.
        if ( $manifest && isset( $manifest[ $file ] ) ) {
            $file = $manifest[ $file ];
        }

        return $this->assetsDirectory . $file;
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest(): array {
        if ( ! is_null( $this->manifest ) ) {
            return $this->manifest;
        }

        $manifestPath = $this->prepareManifestPath();

        if ( ! is_file( $manifestPath ) ) {
            return [];
        }

        $this->manifest = json_decode( (string) file_get_contents( $manifestPath ), true ) ?? [];

        return $this->manifest;
    }

    /**
     * Returns the file path to the `mix-manifest.json` file.
     */
    protected function prepareManifestPath(): string {
        return $this->path( $this->prepareManifestDirectory() . '/' . $this->manifestName );
    }

    /**
     * Prepare manifest directory, considering the manifest directory property.
     *
     * @return string Prepared manifest directory path.
     */
    protected function prepareManifestDirectory() {
        // If custom manifest directory is provided just for this asset,
        // use it, otherwise fallback to class property.
        $manifestDirectory = $this->customManifestDirectory ?: $this->manifestDirectory;

        // If either is missing, use assets directory, as it is the default directory,
        // for `mix-manifest.json` file.
        $manifestDirectory = $manifestDirectory ?: $this->assetsDirectory;

        if ( $manifestDirectory && ! str_starts_with( $manifestDirectory, '/' ) ) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        return $manifestDirectory;
    }

    /**
     * Set the assets directory path.
     *
     * @param $assetsDirectory string Assets directory path.
     */
    public function setAssetsDirectory( $assetsDirectory ): void {
        $this->assetsDirectory = $assetsDirectory;
    }

    /**
     * Set the custom manifest name.
     *
     * @param $manifestName string
     */
    public function setManifestName( $manifestName ): void {
        $this->manifestName = $manifestName;
    }

    /**
     * Set the manifest directory path.
     *
     * @param $manifestDirectory string Manifest directory path.
     */
    public function setManifestDirectory( $manifestDirectory ): void {
        $this->manifestDirectory = $manifestDirectory;
    }

}
