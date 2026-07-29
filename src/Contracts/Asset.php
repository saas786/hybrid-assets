<?php

/**
 * Contract for a fully resolved asset.
 */

namespace Hybrid\Assets\Contracts;

interface Asset {
    /**
     * Relative path this asset was resolved to, e.g. `/public/js/app.js`.
     */
    public function file(): string;

    public function url(): string;

    public function path(): string;

    /**
     * @param array<int, string> $additional Additional dependency handles to merge in.
     *
     * @return array<int, string>
     */
    public function dependencies( array $additional = [] ): array;

    public function version(): ?string;
}
