# Vite Manifest Support for PHP & Twig

[![Version](https://img.shields.io/github/v/release/userfrosting/vite-php-twig?sort=semver)](https://github.com/userfrosting/vite-php-twig/releases)
![PHP Version](https://img.shields.io/badge/php-%5E8.1-brightgreen)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build](https://img.shields.io/github/actions/workflow/status/userfrosting/vite-php-twig/Build.yml?logo=github)](https://github.com/userfrosting/vite-php-twig/actions)
[![Codecov](https://codecov.io/gh/userfrosting/vite-php-twig/branch/main/graph/badge.svg)](https://app.codecov.io/gh/userfrosting/vite-php-twig/branch/main)
[![StyleCI](https://github.styleci.io/repos/821945045/shield?branch=main&style=flat)](https://github.styleci.io/repos/821945045)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/userfrosting/vite-php-twig/PHPStan.yml?label=PHPStan)](https://github.com/userfrosting/vite-php-twig/actions/workflows/PHPStan.yml)
[![Donate](https://img.shields.io/badge/Donate-Buy%20Me%20a%20Coffee-blue.svg)](https://ko-fi.com/lcharette)

Vite Manifest function for PHP & Twig Templates. Allows [Vite manifest](https://vitejs.dev/guide/backend-integration) integration in PHP & Twig Templates without Symfony. Optimized for PHP-DI style containers.

Inspired by [kellerkinderDE/vite-encore-bundle](https://github.com/kellerkinderDE/vite-encore-bundle) & [PHP-Vite](https://github.com/mindplay-dk/php-vite). 

## Installation
```
composer require userfrosting/vite-php-twig
```

## Documentation & Usage
### Using standalone

```php
$manifest = new ViteManifest('.vite/manifest.json');

// Get files for `views/foo.js` entry
$manifest->getScripts('views/foo.js'); // Scripts
$manifest->getStyles('views/foo.js'); // Styles
$manifest->getImports('views/foo.js'); // Preloads

// Render HTML tags for `views/foo.js` entry
$manifest->renderScripts('views/foo.js'); // Scripts
$manifest->renderStyles('views/foo.js'); // Styles
$manifest->renderPreloads('views/foo.js'); // Preloads

// If you have multiple entry point scripts on the same page, you should pass them in a single call to avoid duplicates - for example:
$manifest->getScripts('views/foo.js', 'views/bar.js');
```

> [!TIP]
> `ViteManifest` implements `\UserFrosting\ViteTwig\ViteManifestInterface` if you prefer to type-hint against interfaces, for use with dependency injection.

### Using with Twig
> [!IMPORTANT]
> Requires Twig 3 or newer

Vite writes a `manifest.json` file that contains all of the files needed for each [Rollup Input](https://rollupjs.org/configuration-options/#input) or "entry". To reference entries in Twig, you need to add the `ViteTwigExtension` extension to the Twig Environment. This accepts a `ViteManifest`, which itself accepts the path to the `manifest.json`.

```php
use UserFrosting\ViteTwig\ViteManifest;
use UserFrosting\ViteTwig\ViteTwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$manifest = new ViteManifest('.vite/manifest.json');
$extension = new ViteTwigExtension($manifest);

// Create Twig Environment and add extension
$loader = new FilesystemLoader('./path/to/templates');
$twig = new Environment($loader);
$twig->addExtension($extension);
```

Now, to render all of the `script` and `link` tags for a specific "entry" (e.g. `views/foo.js`), you can:

```twig
{{ vite_js('views/foo.js') }}
{{ vite_css('views/foo.js') }}
{{ vite_preload('views/foo.js') }}
```

If you have multiple entry point scripts on the same page, you should pass them in a single call to avoid duplicates - for example:
```twig
{{ vite_js('views/foo.js', 'views/bar.js') }}
```

Standalone CSS, SCSS, SASS, and LESS files can also be rendered directly, as long as they are defined in your Vite config as a rollup input.
```twig
{{ vite_css('my_theme.less') }}
```

### Vite Default Port
By default, Vite will use port `5173`. However, if the port is already being used, Vite will automatically try the next available port so this may not be the actual port the server ends up listening on. Since a PHP application doesn't know which port is being used by Vite, the port can be forced in the `vite.config.js` file inside your project's root directory using [`server.strictPort`](https://vitejs.dev/config/server-options#server-strictport) and [`server.port`](https://vitejs.dev/config/server-options#server-port):
```js
server: {
    strictPort: true,
    port: 3000,
},
```
For more information on how to configure Vite, see the [official documentation](https://vitejs.dev/config/).

### ViteManifest Options

```php
$manifest = new ViteManifest(
    manifestPath: '.vite/manifest.json',
    basePath: 'dist/',
    serverUrl: 'http://[::1]:5173/',
    devEnabled: true,
);
```

- `manifestPath` - string: Points to the Vite `manifest.json` file created for the production build. Optional if you're using the dev server.
- `basePath` - string: Public base path from which Vite's published assets are served. The assets paths will be relative to the `outDir` in your Vite configuration. It could also point to a CDN or other asset server if you are serving assets from a different domain.
- `serverUrl` - string: The Vite server URL, including port. Can be used to specify a non-default port if used.
- `devEnabled` - bool: Indicates whether the application is running in development mode (i.e. using Vite server). Defaults to false.

## See Also
- [Changelog](CHANGELOG.md)
- [License](LICENSE)

## References
- [Vite manifest](https://vitejs.dev/guide/backend-integration)
- [kellerkinderDE/vite-encore-bundle](https://github.com/kellerkinderDE/vite-encore-bundle)
- [PHP-Vite](https://github.com/mindplay-dk/php-vite)
