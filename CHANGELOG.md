# Changelog

All notable changes to `importmap-laravel` will be documented in this file.

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
