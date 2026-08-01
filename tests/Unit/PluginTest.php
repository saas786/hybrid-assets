<?php

use Hybrid\Assets\ChildTheme;
use Hybrid\Assets\ParentTheme;
use Hybrid\Assets\Plugin;

// -- pluginFile() -------------------------------------------------------------

it( 'throws when no plugin file has been set', function () {
    ( new Plugin )->pluginFile();
} )->throws( LogicException::class, 'No plugin file set on Hybrid\Assets\Plugin.' );

it( 'setPluginFile() is fluent and pluginFile() returns what was set', function () {
    $plugin = new Plugin;

    expect( $plugin->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' ) )->toBe( $plugin )
        ->and( $plugin->pluginFile() )->toBe( '/var/www/plugins/my-plugin/my-plugin.php' );
} );

// -- getOverrideAssetsDirectory() ---------------------------------------------

it( 'defaults the override assets directory to the assets dir + plugin dir name', function () {
    $plugin = ( new Plugin )
        ->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    // Default assets directory is `/public`.
    expect( $plugin->getOverrideAssetsDirectory() )->toBe( '/public/my-plugin' );
} );

it( 'reflects a custom assets directory in the default override directory', function () {
    $plugin = ( new Plugin )
        ->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' )
        ->setAssetsDirectory( 'dist' );

    expect( $plugin->getOverrideAssetsDirectory() )->toBe( '/dist/my-plugin' );
} );

it( 'setOverrideAssetsDirectory() takes precedence over the default', function () {
    $plugin = ( new Plugin )
        ->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' )
        ->setOverrideAssetsDirectory( 'public/custom-name' );

    expect( $plugin->getOverrideAssetsDirectory() )->toBe( '/public/custom-name' );
} );

it( 'setOverrideAssetsDirectory() is fluent', function () {
    $plugin = new Plugin;

    expect( $plugin->setOverrideAssetsDirectory( 'public/x' ) )->toBe( $plugin );
} );

// -- path()/url() --------------------------------------------------------------

it( 'resolves path() via plugin_dir_path()', function () {
    Brain\Monkey\Functions\expect( 'plugin_dir_path' )
        ->once()
        ->with( '/var/www/plugins/my-plugin/my-plugin.php' )
        ->andReturn( '/var/www/plugins/my-plugin/' );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    expect( $plugin->path( '/public/js/app.js' ) )->toBe( '/var/www/plugins/my-plugin/public/js/app.js' );
} );

it( 'path() returns the bare plugin directory for an empty file', function () {
    Brain\Monkey\Functions\expect( 'plugin_dir_path' )
        ->once()
        ->andReturn( '/var/www/plugins/my-plugin/' );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    expect( $plugin->path() )->toBe( '/var/www/plugins/my-plugin/' );
} );

it( 'resolves url() via plugin_dir_url()', function () {
    Brain\Monkey\Functions\expect( 'plugin_dir_url' )
        ->once()
        ->with( '/var/www/plugins/my-plugin/my-plugin.php' )
        ->andReturn( 'https://example.test/wp-content/plugins/my-plugin/' );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    expect( $plugin->url( '/public/js/app.js' ) )
        ->toBe( 'https://example.test/wp-content/plugins/my-plugin/public/js/app.js' );
} );

it( 'url() returns the bare plugin URL for an empty file', function () {
    Brain\Monkey\Functions\expect( 'plugin_dir_url' )
        ->once()
        ->andReturn( 'https://example.test/wp-content/plugins/my-plugin/' );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    expect( $plugin->url() )->toBe( 'https://example.test/wp-content/plugins/my-plugin/' );
} );

it( 'path()/url() strip a leading slash before appending the file', function () {
    Brain\Monkey\Functions\expect( 'plugin_dir_path' )->once()->andReturn( '/var/www/plugins/my-plugin/' );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    // No accidental double slash between the plugin dir and the file.
    expect( $plugin->path( '/js/app.js' ) )->toBe( '/var/www/plugins/my-plugin/js/app.js' );
} );

// -- Inheritance ----------------------------------------------------------------

it( 'falls back to the child theme, then the parent theme', function () {
    $reflection = new ReflectionProperty( Plugin::class, 'inheritance' );
    $reflection->setAccessible( true );

    expect( $reflection->getValue( new Plugin ) )->toBe( [ ChildTheme::class, ParentTheme::class ] );
} );

it( 'looks up inherited files under its override assets directory, not its own', function () {
    // A theme overriding this plugin's assets is expected to host them at
    // `{theme}/public/my-plugin/...`, not `{theme}/public/...` — this is
    // the one piece of behaviour that's specific to Plugin (see
    // AssetsResolver::asset()'s `$this instanceof Plugin` branch).
    $themeRoot = assets_fixture_path( 'theme' );

    mkdir( assets_fixture_path( 'theme/public/my-plugin/js' ), recursive: true, permissions: 0755 );
    file_put_contents( assets_fixture_path( 'theme/public/my-plugin/js/override.js' ), "console.log('override');" );

    $themeResolver = new Hybrid\Assets\Tests\Fixtures\FakeResolver( $themeRoot, 'https://example.test' );

    $container = new Hybrid\Container\Container;
    $container->instance( ChildTheme::class, $themeResolver );
    Hybrid\Container\Container::setInstance( $container );

    $plugin = ( new Plugin )->setPluginFile( '/var/www/plugins/my-plugin/my-plugin.php' );

    $asset = $plugin->asset( 'js/override.js', inherit: true );

    expect( $asset->path() )->toBe( $themeRoot . '/public/my-plugin/js/override.js' );

    Hybrid\Container\Container::setInstance( null );
    delete_directory( assets_fixture_path( 'theme/public/my-plugin' ) );
} );
