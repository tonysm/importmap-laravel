# Changelog

All notable changes to `importmap-laravel` will be documented in this file.

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
