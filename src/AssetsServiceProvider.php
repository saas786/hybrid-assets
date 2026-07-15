<?php

namespace Hybrid\Assets;

use Hybrid\Core\ServiceProvider;

/**
 * Binds the theme/plugin asset resolvers into the container.
 */
class AssetsServiceProvider extends ServiceProvider {
    /**
     * Register.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton( ParentTheme::class );
        $this->app->singleton( ChildTheme::class );

        // Plugin carries per-consumer state (plugin file, override directory,
        // manifest cache) — a singleton here would leak one plugin's config
        // into another's resolution. Every consumer gets its own instance.
        $this->app->bind( Plugin::class );
    }
}
