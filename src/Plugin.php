<?php

/**
 * Asset resolver for a plugin.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Contracts\AssetsAbstract;

class Plugin extends AssetsAbstract {
    /**
     * Absolute path to the plugin's main file (`__FILE__`).
     */
    protected string $pluginFile;

    /**
     * Explicit override assets directory set via `setOverrideAssetsDirectory()`, if any.
     * When empty, `overrideAssetsDirectory()` falls back to the plugin's directory name.
     */
    protected string $overrideAssetsDirectory = '';

    /**
     * Container binding keys checked, in order, when resolving with
     * `$inherit = true`.
     *
     * @var array<int, class-string<AssetsAbstract>>
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
     * Explicitly set the directory used to override this plugin's assets when
     * a theme overrides them (e.g. `my-plugin` -> `{theme}/public/my-plugin/...`).
     *
     * Optional — if never called, the plugin's directory name is used.
     *
     * @param string $overrideAssetsDirectory
     *
     * @return static
     */
    public function setOverrideAssetsDirectory( string $overrideAssetsDirectory ): static {
        $this->overrideAssetsDirectory = trim( $overrideAssetsDirectory, '/' );

        return $this;
    }

    /**
     * Defaults to the plugin's directory name (e.g. `my-plugin` for
     * `wp-content/plugins/my-plugin/my-plugin.php`) unless
     * `setOverrideAssetsDirectory()` was called explicitly.
     */
    public function overrideAssetsDirectory(): string {
        if ( '' !== $this->overrideAssetsDirectory ) {
            return $this->overrideAssetsDirectory;
        }

        return $this->assetsDirectory . '/' . basename( \dirname( $this->pluginFile ) );
    }

    /**
     * Get the filesystem path for a file in the plugin.
     *
     * @param string $file Relative file path.
     *
     * @return string Absolute path.
     */
    public function path( string $file = '' ): string {
        $pluginPath = plugin_dir_path( $this->pluginFile );

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
        $pluginUrl = plugin_dir_url( $this->pluginFile );

        return $file ? $pluginUrl . ltrim( $file, '/' ) : $pluginUrl;
    }
}
