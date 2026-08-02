<?php

/*
|--------------------------------------------------------------------------
| Vendored-sanitizer conformance tests
|--------------------------------------------------------------------------
|
| The dataset below is ported verbatim from WordPress core's own
| `data_sanitize_inline_svg()` (tests/phpunit/tests/icons/wpIconsRegistry.php,
| Trac #64651 / PR #12197). That is the point: these are not our expectations,
| they are core's. If our vendored copy ever diverges from upstream behaviour
| — because we edited it, or because a core change wasn't pulled through —
| these go red with a concrete diff.
|
| Do not "fix" a failure here by editing an expectation. Re-copy it from core,
| or fix the vendored sanitizer. Hand-tuning these to green defeats the entire
| purpose of the file.
|
| Note we call the sanitizer directly. Core's own test needs ReflectionMethod
| because `sanitize_inline_svg()` is private on WP_Icons_Registry; vendoring
| it behind a public static entry point removes that indirection.
|
*/

use Hybrid\Assets\Support\SvgSanitizer;

it( 'matches WordPress core sanitizer expectations', function ( string $input, string $expected ) {
    expect( SvgSanitizer::sanitize( $input ) )->toBe( $expected );
} )->with( function (): array {
    $xlink = ' xmlns:xlink="http://www.w3.org/1999/xlink"';

    return [
        // -- Root selection: exactly one SVG element in the SVG namespace ---
        'rejects multiple top-level svg elements'       => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="first"/></svg><svg xmlns="http://www.w3.org/2000/svg"><path d="second"/></svg>',
            '',
        ],
        'allows nested svg'                             => [
            '<svg xmlns="http://www.w3.org/2000/svg"><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg></svg>',
        ],
        'rejects svg in a foreign namespace'            => [
            '<math><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg></math>',
            '',
        ],
        'returns empty svg when html-like tags present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><p>paragraph content</p><path d="M0 0h24v24H0z" /><div>div content</div></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
        ],
        'handles xmlns:xlink namespace attribute'       => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M0 0h24v24H0z" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"' . $xlink . '><path d="M0 0h24v24H0z" /></svg>',
        ],

        // -- Dangerous content is stripped (wp_kses) -----------------------
        'strips foreignObject but keeps text content'   => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><p>paragraph content</p><script>alert(1)</script></foreignObject><path d="M0 0h24v24H0z" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg">paragraph contentalert(1)<path d="M0 0h24v24H0z" /></svg>',
        ],
        'strips script tags'                            => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><path d="M0 0h24v24H0z" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg">alert(1)<path d="M0 0h24v24H0z" /></svg>',
        ],
        'strips event handlers'                         => [
            '<svg xmlns="http://www.w3.org/2000/svg" onclick="alert(1)"><path d="M0 0h24v24H0z" onload="evil()" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" /></svg>',
        ],
        'strips javascript protocol in href'            => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="javascript:alert(1)" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="alert(1)" /></svg>',
        ],
        'strips data protocol in href'                  => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="data:text/html,<script>alert(1)</script>" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="text/html,&lt;script&gt;alert(1)&lt;/script&gt;" /></svg>',
        ],
        'strips disallowed tags'                        => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/><iframe src="evil"></iframe><object data="x" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],

        // -- Returns empty string when input is not SVG --------------------
        'returns empty for empty string'                => [ '', '' ],
        'returns empty for whitespace only'             => [ "   \n\t  ", '' ],
        'returns empty for plain text'                  => [ 'plain text without svg', '' ],
        'returns empty for html without svg'            => [ '<div>not svg</div><p>content</p>', '' ],

        // -- Content surrounding the SVG root is ignored -------------------
        'ignores content preceding the svg'             => [
            '<p>before</p><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],
        'ignores content following the svg'             => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg><p>after</p>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],
        'ignores an xml declaration before the svg'     => [
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],
        'ignores a comment before the svg'              => [
            '<!-- Generator: some editor --><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],
        'ignores whitespace before the svg'             => [
            "  \n\t<svg xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M0 0\" /></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" /></svg>',
        ],

        // -- Root SVG element ----------------------------------------------
        'preserves root svg element'                    => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" width="24" height="24" class="icon" aria-hidden="true"><path d="M0 0" fill="currentColor" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"' . $xlink . ' viewbox="0 0 24 24" preserveaspectratio="xMidYMid meet" width="24" height="24" class="icon" aria-hidden="true"><path d="M0 0" fill="currentColor" /></svg>',
        ],

        // -- Basic shape elements ------------------------------------------
        'preserves basic shape elements'                => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" /><circle cx="12" cy="12" r="10" /><ellipse cx="12" cy="12" rx="10" ry="8" /><line x1="0" y1="0" x2="24" y2="24" /><polygon points="0,0 24,0 12,24" /><polyline points="0,0 12,12 24,0" /><rect x="2" y="2" width="20" height="20" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" /><circle cx="12" cy="12" r="10" /><ellipse cx="12" cy="12" rx="10" ry="8" /><line x1="0" y1="0" x2="24" y2="24" /><polygon points="0,0 24,0 12,24" /><polyline points="0,0 12,12 24,0" /><rect x="2" y="2" width="20" height="20" /></svg>',
        ],

        // -- Grouping and structural elements ------------------------------
        'preserves grouping and structural elements'    => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><symbol id="icon" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" /></symbol><clipPath id="clip"><circle cx="12" cy="12" r="10" /></clipPath><mask id="m"><rect fill="white" width="24" height="24" /></mask></defs><g><use href="#icon" /><use href="https://example.com/icon.svg#symbol" /><use href="#symbol" /></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><symbol id="icon" viewbox="0 0 24 24"><path d="M0 0h24v24H0z" /></symbol><clipPath id="clip"><circle cx="12" cy="12" r="10" /></clipPath><mask id="m"><rect fill="white" width="24" height="24" /></mask></defs><g><use href="#icon" /><use href="https://example.com/icon.svg#symbol" /><use href="#symbol" /></g></svg>',
        ],
        'preserves switch element'                      => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><path d="M0 0h24v24H0z" /></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><path d="M0 0h24v24H0z" /></switch></svg>',
        ],
        'preserves view element'                        => [
            '<svg xmlns="http://www.w3.org/2000/svg"><view id="v" viewBox="0 0 24 24" /><path d="M0 0h24v24H0z" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><view id="v" viewbox="0 0 24 24" /><path d="M0 0h24v24H0z" /></svg>',
        ],
        'preserves linking element'                     => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="https://example.com"><path d="M0 0h24v24H0z" /></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="https://example.com"><path d="M0 0h24v24H0z" /></a></svg>',
        ],

        // -- Gradient elements ---------------------------------------------
        'preserves gradient elements'                   => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="lin"><stop offset="0%" stop-color="red" /><stop offset="100%" stop-color="blue" /></linearGradient><radialGradient id="rad"><stop offset="0%" stop-color="red" /><stop offset="100%" stop-color="blue" /></radialGradient><rect fill="url(#lin)" width="24" height="24" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="lin"><stop offset="0%" stop-color="red" /><stop offset="100%" stop-color="blue" /></linearGradient><radialGradient id="rad"><stop offset="0%" stop-color="red" /><stop offset="100%" stop-color="blue" /></radialGradient><rect fill="url(#lin)" width="24" height="24" /></svg>',
        ],

        // -- Pattern element -----------------------------------------------
        'preserves pattern element'                     => [
            '<svg xmlns="http://www.w3.org/2000/svg"><pattern id="pat" width="4" height="4"><rect width="4" height="4" fill="currentColor" /></pattern><rect fill="url(#pat)" width="24" height="24" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><pattern id="pat" width="4" height="4"><rect width="4" height="4" fill="currentColor" /></pattern><rect fill="url(#pat)" width="24" height="24" /></svg>',
        ],

        // -- Filter elements -----------------------------------------------
        'preserves filter elements'                     => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="blur"><feGaussianBlur in="SourceGraphic" stdDeviation="1" /></filter><rect filter="url(#blur)" width="24" height="24" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="blur"><feGaussianBlur in="SourceGraphic" stddeviation="1" /></filter><rect filter="url(#blur)" width="24" height="24" /></svg>',
        ],

        // -- Text elements --------------------------------------------------
        'preserves text elements'                       => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0,20 Q12,0 24,20" /><text x="12" y="16" text-anchor="middle">A<tspan font-weight="bold">B</tspan></text><text><textPath href="#p">path</textPath></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0,20 Q12,0 24,20" /><text x="12" y="16" text-anchor="middle">A<tspan font-weight="bold">B</tspan></text><text><textPath href="#p">path</textPath></text></svg>',
        ],

        // -- Descriptive elements -------------------------------------------
        'preserves descriptive elements'                => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>Icon title</title><desc>Description</desc><metadata></metadata><path d="M0 0h24v24H0z" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>Icon title</title><desc>Description</desc><metadata></metadata><path d="M0 0h24v24H0z" /></svg>',
        ],

        // -- Image element ---------------------------------------------------
        'preserves image element'                       => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="https://example.com/icon.png" width="24" height="24" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="https://example.com/icon.png" width="24" height="24" /></svg>',
        ],

        // -- Marker element --------------------------------------------------
        'preserves marker element'                      => [
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="arrow" refX="10" refY="5"><path d="M0,0 L10,5 L0,10" /></marker><path d="M0,12 L24,12" marker-start="url(#arrow)" /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="arrow" refx="10" refy="5"><path d="M0,0 L10,5 L0,10" /></marker><path d="M0,12 L24,12" marker-start="url(#arrow)" /></svg>',
        ],

        // -- Animation elements ----------------------------------------------
        'preserves animation elements'                  => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="opacity" from="1" to="0.5" dur="1s" /><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="2s" /><path d="M0,0 L10,10"><animateMotion path="M0,0 L24,24" dur="1s" /></path><path d="M0 0"><set attributeName="opacity" to="0.5" begin="1s" /></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributename="opacity" from="1" to="0.5" dur="1s" /><animateTransform attributename="transform" type="rotate" from="0 12 12" to="360 12 12" dur="2s" /><path d="M0,0 L10,10"><animateMotion path="M0,0 L24,24" dur="1s" /></path><path d="M0 0"><set attributename="opacity" to="0.5" begin="1s" /></path></svg>',
        ],

        // -- Processor cannot fully parse -------------------------------------
        'returns empty when paused on incomplete token' => [
            '<svg><path d="M0 0"',
            '',
        ],
        'returns empty when processor errors on unsupported markup' => [
            '<svg><foreignObject><table>TEXT NOT SUPPORTED HERE!',
            '',
        ],
    ];
} );
