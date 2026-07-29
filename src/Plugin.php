<?php

/**
 * Asset resolver for a plugin.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Exceptions\PluginFileNotSetException;
use function Hybrid\Tools\blank;

class Plugin extends AssetsResolver {
    /**
     * Absolute path to the plugin's main file (`__FILE__`).
     */
    protected string $pluginFile = '';

    /**
     * Explicit override assets directory set via `setOverrideAssetsDirectory()`, if any.
     * When empty, `getOverrideAssetsDirectory()` falls back to the plugin's directory name.
     */
    protected string $overrideAssetsDirectory = '';

    /**
     * Container binding keys checked, in order, when resolving with
     * `$inherit = true`.
     *
     * @var array<int, class-string<\Hybrid\Assets\Contracts\AssetsResolver>>
     */
    protected array $inheritance = [
        ChildTheme::class,
        ParentTheme::class,
    ];

    /**
     * Set the plugin's main file.
     *
     * @param string $pluginFile Absolute path to the plugin's main file (`__FILE__`).
     *
     * @return static
     */
    public function setPluginFile( string $pluginFile ): static {
        $this->pluginFile = $pluginFile;

        return $this;
    }

    /**
     * Get the plugin's main file, guaranteeing it has been configured.
     *
     * @throws PluginFileNotSetException When `setPluginFile()` was never called.
     */
    public function pluginFile(): string {
        if ( '' === $this->pluginFile ) {
            throw PluginFileNotSetException::forResolver( static::class );
        }

        return $this->pluginFile;
    }

    /**
     * Explicitly set the directory, relative to the theme root, used when a
     * theme overrides this plugin's assets — e.g. `/public/my-plugin` resolves
     * overrides at `{theme}/public/my-plugin/...`. The value is used verbatim,
     * so it must include the theme's own assets directory if it has one.
     *
     * Optional — if never called, this plugin's assets directory plus the
     * plugin's own directory name is used.
     *
     * @param string $overrideAssetsDirectory
     *
     * @return static
     */
    public function setOverrideAssetsDirectory( string $overrideAssetsDirectory ): static {
        // Empty directory path is not allowed.
        if ( blank( $overrideAssetsDirectory ) ) {
            return $this;
        }

        $this->overrideAssetsDirectory = $this->normalizeDirectory( $overrideAssetsDirectory );

        return $this;
    }

    /**
     * Defaults to the plugin's directory name (e.g. `my-plugin` for
     * `wp-content/plugins/my-plugin/my-plugin.php`) unless
     * `setOverrideAssetsDirectory()` was called explicitly.
     */
    public function getOverrideAssetsDirectory(): string {
        if ( '' !== $this->overrideAssetsDirectory ) {
            return $this->overrideAssetsDirectory;
        }

        return $this->getAssetsDirectory() . '/' . basename( \dirname( $this->pluginFile() ) );
    }

    /**
     * Get the filesystem path for a file in the plugin.
     *
     * @param string $file Relative file path.
     *
     * @return string Absolute path.
     */
    public function path( string $file = '' ): string {
        $pluginPath = plugin_dir_path( $this->pluginFile() );

        return $file ? $pluginPath . ltrim( $file, '/' ) : $pluginPath;
    }

    /**
     * Get the URL for a file in the plugin.
     *
     * @param string $file Relative file path.
     *
     * @return string File URL.
     */
    public function url( string $file = '' ): string {
        $pluginUrl = plugin_dir_url( $this->pluginFile() );

        return $file ? $pluginUrl . ltrim( $file, '/' ) : $pluginUrl;
    }
}
