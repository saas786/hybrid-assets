<?php

/**
 * Asset resolver for the parent theme.
 */

namespace Hybrid\Assets;

class ParentTheme extends AssetsResolver {
    /**
     * Container binding keys checked, in order, when resolving with
     * `$inherit = true`. A parent theme only ever falls back to its child.
     *
     * @var array<int, class-string<\Hybrid\Assets\Contracts\AssetsResolver>>
     */
    protected array $inheritance = [
        ChildTheme::class,
    ];

    /**
     * Always true — WordPress can't run without a parent theme active.
     */
    public function exists(): bool {
        return true;
    }

    /**
     * Get the filesystem path for a file in the parent theme.
     *
     * @param string $file Relative file path.
     *
     * @return string Absolute path.
     */
    public function path( string $file = '' ): string {
        return get_parent_theme_file_path( $file );
    }

    /**
     * Get the URL for a file in the parent theme.
     *
     * @param string $file Relative file path.
     *
     * @return string File URL.
     */
    public function url( string $file = '' ): string {
        return get_parent_theme_file_uri( $file );
    }
}
