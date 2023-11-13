# Changelog

All notable changes to `importmap-laravel` will be documented in this file.

## 1.8.0 - 2023-11-13

### What's Changed

- Fix installation script not properly fixing paths resolution by @tonysm in https://github.com/tonysm/importmap-laravel/pull/34
- Bump shims version to 1.8.2 by @tonysm in https://github.com/tonysm/importmap-laravel/pull/35

**Full Changelog**: https://github.com/tonysm/importmap-laravel/compare/1.7.0...1.8.0

## 1.7.0 - 2023-11-12

### What's Changed

- Adds a `bin/importmap` script by @tonysm in https://github.com/tonysm/importmap-laravel/pull/33

**Full Changelog**: https://github.com/tonysm/importmap-laravel/compare/1.6.0...1.7.0

## 1.6.0 - 2023-07-27

### Changelog

- **CHANGED**: Push symlinks config to package instead of patching the application's `config/filesystems.php` file by @tonysm in https://github.com/tonysm/importmap-laravel/pull/29

**Full Changelog**: https://github.com/tonysm/importmap-laravel/compare/1.5.0...1.6.0

## 1.5.0 - 2023-07-14

### Changelog

- **NEW**: New `importmap:packages` command that lists out the external packages being imported
- **FIXED**: Fixes single quotes support in the `routes/importmap.php` file

**Full Changelog**: https://github.com/tonysm/importmap-laravel/compare/1.4.1...1.5.0

## 1.4.1 - 2023-05-10

### What's Changed

- Bump the shim version to 1.7.2

**Full Changelog**: https://github.com/tonysm/importmap-laravel/compare/1.4.0...1.4.1

## 1.4.0 - 2023-02-14

### Changelog

- **CHANGED**: Bumps the default `es-module-shims` version to `1.3.1`
- **CHANGED**: Support Laravel 10

## 1.3.1 - 2023-02-14

### Changelog

- **CHANGED**: Bumps the default `es-module-shims` version to `1.3.1`
- **CHANGED**: Support Laravel 10

## 1.3.0 - 2022-12-28

### Changelog

- **CHANGED**: Bumped `es-module-shims` version to `1.6.2` (latest) and make it configurable so applications may bump it without having to upgrade the package

## 1.2.3 - 2022-08-04

### Changelog

- **FIXED**: Fixes the optimize command when pinning dependencies from `public/vendor` (https://github.com/tonysm/importmap-laravel/commit/a3a685583bfaaf82e737f0ec2fb368f63f3d3c1f)

## 1.2.2 - 2022-08-04

### Changelog

- **CHANGED**: stop escapeing the slashes in the `importmap:json` output (https://github.com/tonysm/importmap-laravel/commit/496cb8bc77c51fd1dae28f12e37a881b4cc41997)
- **NEW**: handle imported files from `public/vendor` folder (https://github.com/tonysm/importmap-laravel/commit/b6c22d1f047715b1f47393dc55a59730397aa55a)

## 1.2.1 - 2022-07-29

### Changelog

- **CHANGED**: we don't delete the `public/js` folder anymore, but instead ask the developer to do so (https://github.com/tonysm/importmap-laravel/commit/f0b3ad562bb748fe20f34768d8b9fb49936099c7)

## 1.2.0 - 2022-07-03

### Changelog

- **CHANGED**: The `importmap:install` command was changed to work with the new Vite setup in Laravel. It should also still work on installs in the Laravel 8 frontends setups using Mix.

## 1.1.1 - 2022-06-30

### Changelog

- **FIXED**: The `importmap:pin` command was breaking depending on the package name because we needed to wrap the package name using the `preg_quote` to escape it. Otherwise, some characters might become part of the regex itself. https://github.com/tonysm/importmap-laravel/pull/16

## 1.1.0 - 2022-06-27

### Changelog

- Bumps `es-module-shims` to version 1.5.8

## 0.4.1 - 2022-02-13

### Changelog

- **FIXED**: Pinned directories were not working on Windows because we're using `/` instead of `\`. Anyways, that should be fixed now. Define the directories with `/` as you would on any Unix/Linux OS and the package will make sure that gets converted to the correct directory separator when dealing with file paths and to the `/` separator when dealing with URIs https://github.com/tonysm/importmap-laravel/pull/5

## 0.4.0 - 2022-02-13

### Changelog

- **CHANGED**: Changes the manifest filename to be `.importmap-manifest.json` (with a dot prefix) so it can be included in the Vapor artifact (which doesn't remove dotfiles by default).

## 0.3.0 - 2022-02-09

### Changelog

- **CHANGED**: Laravel 9 support (nothing really changed in the app, just the version constraints)

## 0.2.0 - 2022-01-27

### Changed

- **FIXED**: The manifest already had the final asset URL on it, which is handled by the optimize command, so we don't need to call the asset resolver when the manifest exists
- **NEW**: Added an `AssetResolver` invokable class which should add a `?digest=$HASH` to the asset URL, which is useful for cache busting while in local development. This won't be used in production as the optimize command already generates the full URLs there, which means the `AssetResolver` won't be called
- **CHANGED**: The `entrypoint` was made optional and it defaults to the `app` module, which matches the "entrypoint" file in the default Laravel install (`resources/js/app.js`)

## 1.0.0 - 202X-XX-XX

- initial release
