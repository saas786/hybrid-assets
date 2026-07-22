# Change Log

You can see the changes made via the [commit log](https://github.com/themehybrid/hybrid-assets/commits/master) for the latest release.

## [1.0.0-alpha.4] - 2026-07-15

### Added

- `Asset` value object — resolves a file's URL, path, dependencies, and version in one call via the new `asset()` method
- `.asset.php` (wp-scripts) metadata support, including dependency resolution
- Mix manifest (`mix-manifest.json`) fallback for version resolution when no `.asset.php` file exists
- `Plugin::setOverrideAssetsDirectory()` — lets themes override a plugin's assets under a configurable path
- `ChildTheme::exists()` and explicit per-class inheritance chains driving `$inherit` lookups

### Changed

- `assetUrl()` / `assetPath()` now accept `(string $file, bool $inherit = false)` instead of a manifest directory override
- `Plugin` is now a non-shared container binding instead of a singleton, so multiple plugins don't share state
- Minimum PHP requirement raised to 8.2
- Renamed `Provider` to `AssetsServiceProvider`

### Removed

- `prepareAsset()` manifest-based file rewriting, replaced by `AssetMetaData`'s dedicated `.asset.php` / Mix manifest resolution

## [1.0.0-alpha.3] - 2024-11-29

### Changed

- Add $inherit parameter to handle child theme asset fallback in ParentTheme class

## [1.0.0-alpha.2] - 2024-08-02

### Changed

- Add composer sort-packages configuration

## [1.0.0-alpha.1] - 2023-10-04

### Added

- Launch.  Everything's new!
