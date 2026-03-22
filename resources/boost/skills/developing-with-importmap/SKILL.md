---
name: developing-with-importmap
description: Work with Importmap Laravel — a replacement for npm/yarn that uses ESM import maps. Pin dependencies, configure pins, optimize for production, and manage the importmap workflow.
---

# Importmap Laravel Development

## When to use this skill

Use this skill when adding or managing JavaScript dependencies, configuring the importmap, working with the Blade tags component, or preparing importmap assets for production.

## Pinning Dependencies

Pin a package (downloads it to `resources/js/vendor/` and adds a pin to `routes/importmap.php`):

```bash
php artisan importmap:pin alpinejs
# or using the bin wrapper:
bin/importmap pin alpinejs
```

The `--from` flag selects a CDN provider for resolution (via the JSPM API). Supported providers:

- `jspm` (default) — jspm.io CDN
- `unpkg` — unpkg.com
- `skypack` — Skypack CDN
- `jsdelivr` — jsDelivr CDN

Example: `php artisan importmap:pin alpinejs --from=unpkg`

## The Pins File

Dependencies are declared in `routes/importmap.php`:

```php
use Tonysm\ImportmapLaravel\Facades\Importmap;

Importmap::pin("app", to: "/js/app.js");
Importmap::pin("alpinejs", to: "/js/vendor/alpinejs.js"); // @3.8.1
```

Pin an entire directory (auto-maps all `.js`/`.jsm` files):

```php
Importmap::pinAllFrom("resources/js/", to: "js/");
```

When using `pinAllFrom`, an `index.js` inside a folder becomes the module name of that folder (e.g., `resources/js/libs/index.js` becomes `libs`).

## Blade Component

Add `<x-importmap::tags />` inside `<head>`. It renders the importmap JSON, modulepreload links, and the entrypoint script tag.

```blade
<x-importmap::tags />
{{-- Custom entrypoint: --}}
<x-importmap::tags entrypoint="admin" />
{{-- With CSP nonce: --}}
<x-importmap::tags :nonce="csp_nonce()" />
```

## Preloading

Modules are preloaded by default. To lazy-load a dependency, set `preload: false`:

```php
Importmap::pin("alpinejs", to: "/js/vendor/alpinejs.js", preload: false);
```

## Artisan Commands

- `importmap:install` — Scaffold the importmap setup in a Laravel app.
- `importmap:pin {packages}` — Pin (download and register) one or more packages.
- `importmap:unpin {packages}` — Remove pinned packages.
- `importmap:update` — Update outdated pinned packages to their latest versions.
- `importmap:outdated` — List packages that have newer versions available.
- `importmap:audit` — Run a security audit against the NPM advisory database.
- `importmap:packages` — Display all pinned packages with their versions.
- `importmap:json` — Output the generated importmap JSON.
- `importmap:optimize` — Publish assets with content-based digests for production.
- `importmap:clear` — Remove the optimization manifest and published assets.

## Production

Run `php artisan importmap:optimize` in your deploy pipeline. This fingerprints JS files into `public/dist/js/` and generates `.importmap-manifest.json`. Run `php artisan importmap:clear` to reset.

Add to `.gitignore`:

```
/public/dist
*importmap-manifest.json
```

## Local Development

Run `php artisan storage:link` to symlink `resources/js` to `public/js`. The symlink is only registered in local environments.
