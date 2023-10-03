<?php
/**
 * Class for handling assets for the parent theme.
 */

namespace Hybrid\Assets;

use Hybrid\Assets\Contracts\AssetsAbstract;

/**
 * Class ParentTheme
 * Handles assets for the parent theme.
 */
class ParentTheme extends AssetsAbstract {

    /**
     * Get the filesystem path for a file within the parent theme.
     *
     * @param string $file File path within the parent theme.
     * @return string Absolute filesystem path of the file.
     */
    public function path( $file = '' ): string {
        return get_parent_theme_file_path( $file );
    }

    /**
     * Get the URL for a file within the parent theme.
     *
     * @param string $file File path within the parent theme.
     * @return string URL of the file.
     */
    public function url( $file = '' ): string {
        return get_parent_theme_file_uri( $file );
    }

}
