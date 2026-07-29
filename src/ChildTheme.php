<?php

/**
 * Asset resolver for the child theme.
 */

namespace Hybrid\Assets;

use function Hybrid\Tools\WordPress\get_child_theme_file_path;
use function Hybrid\Tools\WordPress\get_child_theme_file_uri;

class ChildTheme extends AssetsResolver {
    /**
     * Whether a child theme is actually active.
     */
    public function exists(): bool {
        return is_child_theme();
    }

    /**
     * Get the filesystem path for a file in the child theme.
     *
     * @param string $file Relative file path.
     *
     * @return string Absolute path.
     */
    public function path( string $file = '' ): string {
        return get_child_theme_file_path( $file );
    }

    /**
     * Get the URL for a file in the child theme.
     *
     * @param string $file Relative file path.
     *
     * @return string File URL.
     */
    public function url( string $file = '' ): string {
        return get_child_theme_file_uri( $file );
    }
}
