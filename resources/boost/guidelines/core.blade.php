## Importmap Laravel

- Importmap Laravel lets you use ESM modules in Laravel without bundling or transpiling — no Webpack, Vite, npm, or yarn needed.
- **Do NOT use npm, yarn, or any Node.js package manager.** Use the `importmap:pin` Artisan command (or the `bin/importmap` wrapper) to add JavaScript dependencies. All pinned dependencies are downloaded to `resources/js/vendor/` and should be committed to version control.
- Dependencies are declared in `routes/importmap.php` using `Importmap::pin()` and `Importmap::pinAllFrom()`.
- Add `<x-importmap::tags />` inside the `<head>` tag of your layout to render the importmap, modulepreload links, and the entrypoint script.
- JSX (React) and Vue SFCs are NOT supported — they require a compilation step incompatible with importmaps.
- `process.env` is NOT available. Use `<meta>` tags and `document.head.querySelector('meta[name=...]').content` for JS configuration values.
- Version comments (e.g., `// @3.8.1`) on pin lines are used by `importmap:outdated` and `importmap:update` — do not remove them.
- IMPORTANT: When adding, removing, or updating JavaScript dependencies, or configuring the importmap for production, activate the `developing-with-importmap` skill for detailed guidance.
