<?php

use Hybrid\Assets\ChildTheme;
use Hybrid\Assets\ParentTheme;

it( 'always exists', function () {
    expect( ( new ParentTheme )->exists() )->toBeTrue();
} );

it( 'resolves path() via get_parent_theme_file_path()', function () {
    Brain\Monkey\Functions\expect( 'get_parent_theme_file_path' )
        ->once()
        ->with( '/public/js/app.js' )
        ->andReturn( '/var/www/theme/public/js/app.js' );

    $theme = new ParentTheme;

    expect( $theme->path( '/public/js/app.js' ) )->toBe( '/var/www/theme/public/js/app.js' );
} );

it( 'resolves url() via get_parent_theme_file_uri()', function () {
    Brain\Monkey\Functions\expect( 'get_parent_theme_file_uri' )
        ->once()
        ->with( '/public/js/app.js' )
        ->andReturn( 'https://example.test/wp-content/themes/theme/public/js/app.js' );

    $theme = new ParentTheme;

    expect( $theme->url( '/public/js/app.js' ) )
        ->toBe( 'https://example.test/wp-content/themes/theme/public/js/app.js' );
} );

it( 'defaults path()/url() to an empty file', function () {
    Brain\Monkey\Functions\expect( 'get_parent_theme_file_path' )->once()->with( '' )->andReturn( '/var/www/theme/' );
    Brain\Monkey\Functions\expect( 'get_parent_theme_file_uri' )->once()->with( '' )->andReturn( 'https://example.test/theme/' );

    $theme = new ParentTheme;

    expect( $theme->path() )->toBe( '/var/www/theme/' )
        ->and( $theme->url() )->toBe( 'https://example.test/theme/' );
} );

it( 'only falls back to the child theme in its inheritance chain', function () {
    $reflection = new ReflectionProperty( ParentTheme::class, 'inheritance' );
    $reflection->setAccessible( true );

    expect( $reflection->getValue( new ParentTheme ) )->toBe( [ ChildTheme::class ] );
} );
