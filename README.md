#  Importmap Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tonysm/importmap-laravel.svg?style=flat-square)](https://packagist.org/packages/tonysm/importmap-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/tonysm/importmap-laravel/run-tests?label=tests)](https://github.com/tonysm/importmap-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/tonysm/importmap-laravel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/tonysm/importmap-laravel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/tonysm/importmap-laravel.svg?style=flat-square)](https://packagist.org/packages/tonysm/importmap-laravel)

## Introduction

Use ESM with importmap to manage modern JavaScript in Laravel without transpiling or bundling.

### Inspiration

This package was inspired by the [Importmap Rails](https://github.com/rails/importmap-rails) gem. Some pieces of this README were copied straight from there and adapted to the Laravel version.

### How does it work?

[Import maps](https://github.com/WICG/import-maps) let you import JavaScript modules using logical names that map to versioned/digested files – directly from the browser. So you can [build modern JavaScript applications using JavaScript libraries made for ES modules (ESM) without the need for transpiling or bundling](https://world.hey.com/dhh/modern-web-apps-without-javascript-bundling-or-transpiling-a20f2755). This frees you from needing Webpack, Yarn, npm, or any other part of the JavaScript toolchain.

With this approach you'll ship many small JavaScript files instead of one big JavaScript file. Thanks to HTTP/2 that no longer carries a material performance penalty during the initial transport, and in fact offers substantial benefits over the long run due to better caching dynamics. Whereas before any change to any JavaScript file included in your big bundle would invalidate the cache for the the whole bundle, now only the cache for that single file is invalidated.

There's [native support for import maps in Chrome/Edge 89+](https://caniuse.com/?search=importmap), and [a shim available](https://github.com/guybedford/es-module-shims) for any browser with basic ESM support. So your app will be able to work with all the evergreen browsers.

## Installation

You can install the package via composer:

```bash
composer require tonysm/importmap-laravel
```

The package has an `install` command that you may run to replace the default Laravel scaffold with one to use importmap:

```bash
php artisan importmap:install
```

Next, we need to add the following component to our view or layout file:

```blade
<x-importmap-tags entrypoint="app" />
```

Add that between your `<head>` tags. The `entrypoint` should be the "main" file, commonly the `resources/js/app.js` file, which will be mapped to the `app` module (use the module name, not the file).

We also need to symlink the `resources/js` folder to `public/js` to make our JavaScript files publicly available. It's recommended to do that only for local development. This can be achieved by adding the link rule to your `config/filesystems.php`:

```php
<?php

return [
    // ...
    'links' => array_filter([
        public_path('storage') => storage_path('app/public'),
        public_path('js') => env('APP_ENV') === 'local' ? resource_path('js') : null,
    ]),
];
```

Now, whenever you run `php artisan storage:link` in the `local` env, your `resources/js` folder will be linked to the `public/js` folder, which will make your imports work while you're developing your app.

For production, it's recommended to run the `importmap:optimize` command instead:

```php
php artisan importmap:optimize
```

This should scan all your pinned files/folders (no URLs) and publish them to `public/dist/js`, adding a digest based on the file's content to the file name - so something like `public/dist/js/app-123123.js`, and then generate an `importmap-manifest.json` file in the `public/` folder. This file will get precence over your pins. If you run that by accident in development, make sure you delete that file or simply run `php artisan importmap:clear`, which should get rid of it. You may also want to add `/public/dist` to your `.gitignore` file.

## Usage

In a nutshell, importmaps works by giving the browser map of where to look for your JavaScript import statements. For instance, you could _pin_ a dependency in the `routes/importmap.php` file for Alpinejs like so:

```php
<?php

use Tonysm\ImportmapLaravel\Facades\Importmap;

// Other pins...
Importmap::pin("alpinejs", to: "https://ga.jspm.io/npm:alpinejs@3.8.1/dist/module.esm.js");
```

Then, in your JavaScript files you can safely do:

```js
import Alpine from 'alpinejs';

Alpine.start();
window.Alpine = Alpine;
```

### Pinning Local Files

Local pins should be added to the `routes/importmap.php` file manually, like so:

```php
Importmap::pin("app", to: "/js/app.js");
```

This means that the `app` module will point to `/js/app.js` in the browser. This is a URL or a URI, not the path to file itself.

### Pinning Local Directories

Declaring all your local files can be tedious, so you may want to map an entire folder like so:

```php
Importmap::pinAllFrom("resources/js/", to: "js/");
```

When we're generating the importmap JSON, we'll scan that directory looking for any `.js` or `.jsm` files inside of it and generating the correct importmap for them based on their relative location. There are a couple interesting rules, though, something like:

| Path | Module | URI |
|---|---|---|
| `resources/js/app.js` | `app` | `/js/app.js` |
| `resources/js/controllers/hello_controller.js` | `controllers/hello_controller` | `/js/controllers/hello_controller.js` |
| `resources/js/libs/index.js` | `libs` | `/js/libs/index.js` |

If there's an `index.js` file in a folder, we won't get `index` in the module name, so we can import it like

```js
import libs from 'libs';
```

Instead of

```js
import libs from 'libs/index';
```

### Pinning External Dependencies

If you depend on any external library you can use the `importmap:pin` command to pin it, like so:

```bash
php artisan importmap:pin alpinejs
```

That will add the following line to your `routes/importmap.php` file:

```php
Importmap::pin("alpinejs", to: "https://ga.jspm.io/npm:alpinejs@3.8.1/dist/module.esm.js");
```

The `pin` command makes use of the jspm.io API to resolve the dependencies (and the dependencies of our dependencies), looking for ESM modules that we can pin, and resolving it to a CDN URL. We can control the CDN we want to use by specifying the `--from` flag like so:

```bash
php artisan importmap:pin alpinejs --from=unpkg
```

Which should generate a pin like so:

```php
Importmap::pin("alpinejs", to: "https://unpkg.com/alpinejs@3.8.1/dist/module.esm.js");
```

It's preferred that you always pin from the same CDN, because then your browser will reuse the same SSL handshake when downloading the files (which means they will be downloaded faster).

Alternatively to using CDNs, you may prefer to vendor the libraries yourself, which you can do by using the `--download` flag, like so:

```bash
php artisan importmap:pin alpinejs --download
```

This will resolve the dependencies (and the dependencies of our dependencies) and download all the files to your `resources/js/vendor` folder, which you should add to your version control and maintain yourself. The pin will look like this:

```php
Importmap::pin("alpinejs", to: "/js/vendor/alpinejs.js"); // @3.8.1
```

The version is added as a comment to your pin so you know which version was imported. Don't remove that as it's gonna be useful later on when you need to upgrade your dependencies.

### Preloading Modules

To avoid the waterfall effect where the browser has to load one file after another before it can get to the deepest nested import, we support [modulepreload links](https://developers.google.com/web/updates/2017/12/modulepreload). Pinned modules can be preloaded by appending `preload: true` to the pin, like so:

```php
Importmap::pinAllFrom("resources/js/", to: "js/", preload: true);
Importmap::pin("alpinejs", to: "https://unpkg.com/alpinejs@3.8.1/dist/module.esm.js", preload: true); // @3.8.1
```

Which will add the correct `links` tags to your head tag in the HTML document, like so:

```html
<link rel="modulepreload" href="https://unpkg.com/alpinejs@3.8.1/dist/module.esm.js">
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Tony Messias](https://github.com/tonysm)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
