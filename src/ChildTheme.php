<?php
/**
 * Class for handling assets for the child theme.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Contracts\AssetsAbstract;
use function Hybrid\Tools\WordPress\get_child_theme_file_path;
use function Hybrid\Tools\WordPress\get_child_theme_file_uri;

/**
 * Class ChildTheme
 * Handles assets for the child theme.
 */
class ChildTheme extends AssetsAbstract {

    /**
     * Get the filesystem path for a file within the child theme.
     *
     * @param string $file File path within the child theme.
     * @return string Absolute filesystem path of the file.
     */
    public function path( $file = '' ): string {
        return get_child_theme_file_path( $file );
    }

    /**
     * Get the URL for a file within the child theme.
     *
     * @param string $file File path within the child theme.
     * @return string URL of the file.
     */
    public function url( $file = '' ): string {
        return get_child_theme_file_uri( $file );
    }

}
