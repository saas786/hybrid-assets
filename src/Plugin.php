<?php
/**
 * Class for handling assets for the plugin.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Contracts\AssetsAbstract;

/**
 * Class Plugin
 * Handles assets for the plugin.
 */
class Plugin extends AssetsAbstract {

    /**
     * Plugin file `__FILE__`.
     */
    protected string $plugin_file;

    /**
     * Sets the plugin file.
     *
     * @param string $plugin_file Path to the plugin file.
     */
    public function setPluginFile( string $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    /**
     * Get the filesystem path for a file within the plugin.
     *
     * @param string $file File path within the plugin.
     * @return string Absolute filesystem path of the file.
     */
    public function path( $file = '' ): string {
        $plugin_path = plugin_dir_path( $this->plugin_file );

        if ( $file ) {
            return $plugin_path . ltrim( $file, '/' );
        }

        return $plugin_path;
    }

    /**
     * Get the URL for a file within the plugin.
     *
     * @param string $file File path within the plugin.
     * @return string URL of the file.
     */
    public function url( $file = '' ): string {
        $plugin_url = plugin_dir_url( $this->plugin_file );

        if ( $file ) {
            return $plugin_url . ltrim( $file, '/' );
        }

        return $plugin_url;
    }

}
