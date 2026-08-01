<?php

use Hybrid\Assets\ChildTheme;

/**
 * `Hybrid\Tools\WordPress\get_child_theme_file_path()` and
 * `get_child_theme_file_uri()` are real functions shipped by hybrid-tools
 * (guarded by function_exists, loaded eagerly via Composer). Brain Monkey
 * can only intercept functions that don't already exist, so rather than
 * mocking those two directly, these tests mock the WordPress core functions
 * they're implemented in terms of and let the real helpers run.
 */
function stub_child_theme_filters(): void {
    Brain\Monkey\Functions\when( 'apply_filters' )->returnArg( 2 );
}

it( 'exists() reflects is_child_theme()', function ( bool $isChildTheme ) {
    Brain\Monkey\Functions\when( 'is_child_theme' )->justReturn( $isChildTheme );

    expect( ( new ChildTheme )->exists() )->toBe( $isChildTheme );
} )->with( [ true, false ] );

it( 'resolves path() via the stylesheet directory', function () {
    stub_child_theme_filters();

    Brain\Monkey\Functions\expect( 'get_stylesheet_directory' )
        ->once()
        ->andReturn( '/var/www/child-theme' );

    $theme = new ChildTheme;

    expect( $theme->path( '/public/js/app.js' ) )->toBe( '/var/www/child-theme/public/js/app.js' );
} );

it( 'path() returns the bare stylesheet directory for an empty file', function () {
    stub_child_theme_filters();

    Brain\Monkey\Functions\expect( 'get_stylesheet_directory' )
        ->once()
        ->andReturn( '/var/www/child-theme' );

    expect( ( new ChildTheme )->path() )->toBe( '/var/www/child-theme' );
} );

it( 'resolves url() via the stylesheet directory URI', function () {
    stub_child_theme_filters();

    Brain\Monkey\Functions\expect( 'get_stylesheet_directory_uri' )
        ->once()
        ->andReturn( 'https://example.test/wp-content/themes/child-theme' );

    $theme = new ChildTheme;

    expect( $theme->url( '/public/js/app.js' ) )
        ->toBe( 'https://example.test/wp-content/themes/child-theme/public/js/app.js' );
} );

it( 'has no inheritance chain of its own', function () {
    $reflection = new ReflectionProperty( ChildTheme::class, 'inheritance' );
    $reflection->setAccessible( true );

    expect( $reflection->getValue( new ChildTheme ) )->toBe( [] );
} );
