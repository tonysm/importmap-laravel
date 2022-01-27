# Changelog

All notable changes to `importmap-laravel` will be documented in this file.

## 0.2.0 - 2022-01-27

### Changed

- **FIXED**: The manifest already had the final asset URL on it, which is handled by the optimize command, so we don't need to call the asset resolver when the manifest exists
- **NEW**: Added an `AssetResolver` invokable class which should add a `?digest=$HASH` to the asset URL, which is useful for cache busting while in local development. This won't be used in production as the optimize command already generates the full URLs there, which means the `AssetResolver` won't be called
- **CHANGED**: The `entrypoint` was made optional and it defaults to the `app` module, which matches the "entrypoint" file in the default Laravel install (`resources/js/app.js`)

## 1.0.0 - 202X-XX-XX

- initial release
