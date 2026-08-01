<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Brain Monkey needs to be booted before each test (so WordPress function
| stubs/mocks are available) and torn down after each test (so mocks/
| expectations from one test never leak into the next).
|
*/

uses()
    ->beforeEach( function () {
        \Brain\Monkey\setUp();

        // normalizeDirectory() relies on this in practically every test that
        // touches an AssetsResolver — stub it once, globally, with the real
        // WordPress core behavior rather than mocking it per test.
        \Brain\Monkey\Functions\when( 'wp_normalize_path' )->alias( function ( string $path ): string {
            $path = str_replace( '\\', '/', $path );
            $path = preg_replace( '|(?<=.)/+|', '/', $path );

            if ( ':' === substr( $path, 1, 1 ) ) {
                $path = ucfirst( $path );
            }

            return $path;
        } );
    } )
    ->afterEach( function () {
        \Brain\Monkey\tearDown();
    } )
    ->in( 'Unit' );

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Create a temporary fixture directory (mirroring a theme/plugin root) that
 * is deleted automatically at the end of the test process.
 */
function assets_fixture_path( string $path = '' ): string {
    $base = dirname( __DIR__ ) . '/tests/Fixtures';

    return '' === $path ? $base : $base . '/' . ltrim( $path, '/' );
}

/**
 * Recursively delete a directory. Used to clean up any fixture files a test
 * writes into a temp folder at runtime.
 */
function delete_directory( string $dir ): void {
    if ( ! is_dir( $dir ) ) {
        return;
    }

    $items = scandir( $dir );

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }

        $path = $dir . '/' . $item;

        is_dir( $path ) ? delete_directory( $path ) : unlink( $path );
    }

    rmdir( $dir );
}
