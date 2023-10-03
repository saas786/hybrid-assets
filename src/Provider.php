<?php

namespace Hybrid\Assets;

use Hybrid\Core\ServiceProvider;

/**
 * Assets provider class.
 */
class Provider extends ServiceProvider {

    /**
     * Register.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton( ParentTheme::class );
        $this->app->singleton( ChildTheme::class );
        $this->app->singleton( Plugin::class );
    }

}
