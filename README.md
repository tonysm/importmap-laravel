<p align="center" style="margin-top: 2rem; margin-bottom: 2rem;"><img src="/art/importmap-laravel-logo.svg" alt="Logo Importmap Laravel" /></p>

<p align="center">
    <a href="https://packagist.org/packages/tonysm/importmap-laravel">
        <img src="https://img.shields.io/packagist/dt/tonysm/importmap-laravel.svg?style=flat-square" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/tonysm/importmap-laravel">
        <img src="https://img.shields.io/packagist/v/tonysm/importmap-laravel" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/tonysm/importmap-laravel">
        <img src="https://img.shields.io/packagist/l/tonysm/importmap-laravel" alt="License">
    </a>
</p>

## Introduction

Use ESM with importmap to manage modern JavaScript in Laravel without transpiling or bundling.

### Inspiration

This package was inspired by the [Importmap Rails](https://github.com/rails/importmap-rails) gem. Some pieces of this README were copied straight from there and adapted to the Laravel version.

### How does it work?

[Import maps](https://github.com/WICG/import-maps) let you import JavaScript modules using logical names that map to versioned/digested files – directly from the browser. So you can [build modern JavaScript applications using JavaScript libraries made for ES modules (ESM) without the need for transpiling or bundling](https://world.hey.com/dhh/modern-web-apps-without-javascript-bundling-or-transpiling-a20f2755). This frees you from needing Webpack, Yarn, npm, or any other part of the JavaScript toolchain.

With this approach, you'll ship many small JavaScript files instead of one big JavaScript file. Thanks to HTTP/2 that no longer carries a material performance penalty during the initial transport, and offers substantial benefits over the long run due to better caching dynamics. Whereas before any change to any JavaScript file included in your big bundle would invalidate the cache for the whole bundle, now only the cache for that single file is invalidated.

[Import maps are supported natively in all major, modern browsers](https://caniuse.com/?search=importmap). If you need to work with legacy browsers without native support, you may want to explore using [the shim available](https://github.com/guybedford/es-module-shims).

## Installation

You can install the package via Composer:

```bash
composer require tonysm/importmap-laravel
```

The package has an `install` command that you may run to replace the default Laravel scaffold with one to use importmap:

```bash
php artisan importmap:install
```

Next, we need to add the following component to our view or layout file:

```blade
<x-importmap-tags />
```

Add that between your `<head>` tags. The `entrypoint` should be the "main" file, commonly the `resources/js/app.js` file, which will be mapped to the `app` module (use the module name, not the file).

By default the `x-importmap-tags` component assumes your entrypoint module is `app`, which matches the existing `resources/js/app.js` file from Laravel's default scaffolding. You may want to customize the entrypoint, which you can do with the `entrypoint` prop:

```blade
<x-importmap-tags entrypoint="admin" />
```

The package will automatically map the `resources/js` folder to your `public/js` folder using Laravel's symlink feature. All you have to do after installing the package is run:

```bash
php artisan storage:link
```

If you're using Laravel Sail, make sure you prefix that command with `sail` as the symlink needs to be created inside the container.

The symlink is only registered on local environments. For production, it's recommended to run the `importmap:optimize` command instead:

```php
php artisan importmap:optimize
```

This should scan all your pinned files/folders (no URLs) and publish them to `public/dist/js`, adding a digest based on the file's content to the file name - so something like `public/dist/js/app-123123.js`, and then generate a `.importmap-manifest.json` file in the `public/` folder. This file will get presence over your pins. If you run that by accident in development, make sure you delete that file or simply run `php artisan importmap:clear`, which should get rid of it. You may also want to add `/public/dist` to your `.gitignore` file, as well as `*importmap-manifest.json`.

## Usage

In a nutshell, importmap works by giving the browser a map of where to look for your JavaScript import statements. For instance, you could _pin_ a dependency in the `routes/importmap.php` file for Alpinejs like so:

```php
<?php

use Tonysm\ImportmapLaravel\Facades\Importmap;

// Other pins...
Importmap::pin("alpinejs", to: "https://ga.jspm.io/npm:alpinejs@3.8.1/dist/module.esm.js");
```

Then, in your JavaScript files you can safely do:

```js
import Alpine from 'alpinejs';
```

### Pinning Local Files

Local pins should be added to the `routes/importmap.php` file manually, like so:

```php
Importmap::pin("app", to: "/js/app.js");
```

This means that the `app` module will point to `/js/app.js` in the browser. This is the URI the browser will use to fetch the file, not the path to the file itself. Pins to local files assume a relative path of `resources/js/` to find them.

### Pinning Local Directories

Declaring all your local files can be tedious, so you may want to map an entire folder like so:

```php
Importmap::pinAllFrom("resources/js/", to: "js/");
```

When we're generating the importmap JSON, we'll scan that directory looking for any `.js` or `.jsm` files inside of it and generating the correct importmap for them based on their relative location. There are a couple of interesting rules, though, something like:

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

To avoid the waterfall effect where the browser has to load one file after another before it can get to the deepest nested import, we use [modulepreload links](https://developers.google.com/web/updates/2017/12/modulepreload) by default. If you don't want to preload a dependency, because you want to load it on demand for efficiency, pinned modules can prevent preloading by appending `preload: false` to the pin:

```php
Importmap::pinAllFrom("resources/js/", to: "js/", preload: true);
Importmap::pin("alpinejs", to: "https://unpkg.com/alpinejs@3.8.1/dist/module.esm.js", preload: true); // @3.8.1
```

Which will add the correct `links` tags to your head tag in the HTML document, like so:

```html
<link rel="modulepreload" href="https://unpkg.com/alpinejs@3.8.1/dist/module.esm.js">
```

## Dependency Maintenance Commands

Maintaining a healthy dependency list can be tricky. Here are a couple of commands to help you with this task.

### Outdated Dependencies

To keep your dependencies up-to-date, make sure you run the `importmap:outdated` command from time to time:

```bash
php artisan importmap:outdated
```

This command will scan your `config/importmap.php` file, find your current versions, then use the NPM registry API to look for the latest version of the packages you're using. It also handles locally served vendor libs that you added using the `--download` flag from the `importmap:pin` command.

### Auditing Dependencies

If you want a security audit on your dependencies to see if you're using a version that's been breached, run the `importmap:audit` command from time to time. Better yet, add that command to your CI build:

```bash
php artisan importmap:audit
```

This will also scan your `config/importmap.php` file, find your current versions, then use the NPM registry API to look for vulnerabilities in your packages. It also handles locally served vendor libs that you added using the `--download` flag from the `importmap:pin` command.

## Known Problems

### On React's JSX and Vue's SFC

It's possible to use both React and Vue with importmaps but, unfortunately, you would have to use those without the power of JSX or SFC. That's because those file types need a compilation/transpilation step where they are converted to something the browser can understand. There are alternative ways to use both these libraries, but I should say that these are not "common" ways in their communities. You may use [React with HTM](https://github.com/developit/htm). And you can use Vue just fine without SFC, the only difference is that your templates would be in Blade files, not a SFC file.

### Process ENV Configs

You may be used to having a couple `process.env.MIX_*` lines in your JS files here and there. The way this works is Webpack would replace at build time your calls to `process.env` with the values it had during the build. Since we don't have a "build time" anymore, this won't work. Instead, you should add `<meta>` tags to your layout file with anything that you want to make available to your JavaScript files and use `document.head.querySelector('meta[name=my-config]').content` instead of relying on the `process.env`.

Consider using something like [`current.js`](https://www.npmjs.com/package/current.js) to easily consume your `<meta>` configs using a globally available `Current` object.

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
