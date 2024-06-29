<?php

declare(strict_types=1);

/*
 * Vite Twig Functions
 *
 * @link      https://github.com/userfrosting/vite-php-twig
 * @copyright Copyright (c) 2024 Louis Charette, UserFrosting
 * @license   https://github.com/userfrosting/vite-php-twig/blob/main/LICENSE.md (MIT License)
 */

namespace UserFrosting\ViteTwig\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use UserFrosting\ViteTwig\Exceptions\EntrypointNotFoundException;
use UserFrosting\ViteTwig\Exceptions\ManifestNotFoundException;
use UserFrosting\ViteTwig\ViteManifest;

class ViteManifestTest extends TestCase
{
    protected string $manifestFile = __DIR__ . '/manifests/manifest.json';
    protected ViteManifest $manifest;

    public function testManifestNotFound(): void
    {
        $manifest = new ViteManifest('foo.json');
        $this->expectException(ManifestNotFoundException::class);
        $this->expectExceptionMessage('Manifest `foo.json` not found.');
        $manifest->getScripts('');
    }

    public function testManifestJsonException(): void
    {
        $manifest = new ViteManifest(__DIR__ . '/manifests/emptyManifest.json');
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');
        $manifest->getScripts('');
    }

    public function testEntrypointNotFoundScripts(): void
    {
        $manifest = new ViteManifest($this->manifestFile);
        $this->expectException(EntrypointNotFoundException::class);
        $this->expectExceptionMessage('Entry `views/notFound.js` not found in manifest');
        $manifest->getScripts('views/notFound.js');
    }

    public function testEntrypointNotFoundStyles(): void
    {
        $manifest = new ViteManifest($this->manifestFile);
        $this->expectException(EntrypointNotFoundException::class);
        $this->expectExceptionMessage('Entry `views/notFound.js` not found in manifest');
        $manifest->getStyles('views/bar.js', 'views/notFound.js');
    }

    public function testEntrypointNotFoundImports(): void
    {
        $manifest = new ViteManifest($this->manifestFile);
        $this->expectException(EntrypointNotFoundException::class);
        $this->expectExceptionMessage('Entry `views/notFound.js` not found in manifest');
        $manifest->getImports('views/notFound.js', 'views/bar.js');
    }

    public function testBadManifest(): void
    {
        $manifest = new ViteManifest(__DIR__ . '/manifests/badManifest.json');
        $this->assertSame([], $manifest->getScripts('views/bar.js')); // bar doesn't have file, so be empty
        $this->assertSame([], $manifest->getStyles('views/bar.js')); // bar have a non-existing imports
        $this->assertSame([], $manifest->getImports('views/bar.js')); // bar have a non-existing imports
    }

    public function testGetScripts(): void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $this->assertSame([
            'assets/foo-BRBmoGS9.js'
        ], $manifest->getScripts('views/foo.js'));

        // views/bar.js
        $this->assertSame([
            'assets/bar-gkvgaI9m.js'
        ], $manifest->getScripts('views/bar.js'));

        // Both together
        $this->assertSame([
            'assets/foo-BRBmoGS9.js',
            'assets/bar-gkvgaI9m.js'
        ], $manifest->getScripts('views/foo.js', 'views/bar.js'));
    }

    public function testGetStyles(): void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $this->assertSame([
            'assets/foo-5UjPuW-k.css',
            'assets/shared-ChJ_j-JJ.css'
        ], $manifest->getStyles('views/foo.js'));

        // views/bar.js
        $this->assertSame([
            'assets/shared-ChJ_j-JJ.css'
        ], $manifest->getStyles('views/bar.js'));

        // Both together
        $this->assertSame([
            'assets/foo-5UjPuW-k.css',
            'assets/shared-ChJ_j-JJ.css'
        ], $manifest->getStyles('views/foo.js', 'views/bar.js'));
    }

    public function testImports() :void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $this->assertSame([
            'assets/shared-B7PI925R.js'
        ], $manifest->getImports('views/foo.js'));

        // views/bar.js
        $this->assertSame([
            'assets/shared-B7PI925R.js'
        ], $manifest->getImports('views/bar.js'));

        // Both together
        $this->assertSame([
            'assets/shared-B7PI925R.js'
        ], $manifest->getImports('views/foo.js', 'views/bar.js'));
    }

    public function testBasePath(): void
    {
        $manifest = new ViteManifest(
            manifestPath: $this->manifestFile,
            basePath: 'dist/'
        );

        // views/foo.js
        $this->assertSame([
            'dist/assets/foo-BRBmoGS9.js'
        ], $manifest->getScripts('views/foo.js'));

        // views/bar.js
        $this->assertSame([
            'dist/assets/bar-gkvgaI9m.js'
        ], $manifest->getScripts('views/bar.js'));

        // Both together
        $this->assertSame([
            'dist/assets/foo-BRBmoGS9.js',
            'dist/assets/bar-gkvgaI9m.js'
        ], $manifest->getScripts('views/foo.js', 'views/bar.js'));
    }

    public function testDevServer(): void
    {
        $manifest = new ViteManifest(
            manifestPath: $this->manifestFile,
            devEnabled: true,
        );

        $this->assertSame(['@vite/client', 'views/foo.js'], $manifest->getScripts('views/foo.js'));
        $this->assertSame(['@vite/client', 'views/foo.js', 'views/bar.js'], $manifest->getScripts('views/foo.js', 'views/bar.js'));
        $this->assertSame([], $manifest->getStyles('views/foo.js', 'views/bar.js'));
        $this->assertSame([], $manifest->getImports('views/bar.js'));
    }

    public function testDevServerUrl(): void
    {
        $manifest = new ViteManifest(
            manifestPath: $this->manifestFile,
            devEnabled: true,
            serverUrl: 'http://[::1]:3000/'
        );

        $this->assertSame(['http://[::1]:3000/@vite/client', 'http://[::1]:3000/views/foo.js'], $manifest->getScripts('views/foo.js'));
        $this->assertSame(['http://[::1]:3000/@vite/client', 'http://[::1]:3000/views/foo.js', 'http://[::1]:3000/views/bar.js'], $manifest->getScripts('views/foo.js', 'views/bar.js'));
    }

    /** Base path have no effect on server URL */
    public function testDevServerUrlAndBasePath(): void
    {
        $manifest = new ViteManifest(
            manifestPath: $this->manifestFile,
            devEnabled: true,
            serverUrl: 'http://[::1]:3000/',
            basePath: 'dist/'
        );

        $this->assertSame(['http://[::1]:3000/@vite/client', 'http://[::1]:3000/views/foo.js'], $manifest->getScripts('views/foo.js'));
        $this->assertSame(['http://[::1]:3000/@vite/client', 'http://[::1]:3000/views/foo.js', 'http://[::1]:3000/views/bar.js'], $manifest->getScripts('views/foo.js', 'views/bar.js'));
    }

    public function testRenderScripts(): void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $expected = [
            '<script type="module" src="assets/foo-BRBmoGS9.js"></script>'
        ];

        $result = $manifest->renderScripts('views/foo.js');
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<script type="module" src="assets/bar-gkvgaI9m.js"></script>'
        ];

        $result = $manifest->renderScripts('views/bar.js');
        $this->assertSame(implode('', $expected), $result);
    }

    public function testLinkTags(): void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $expected = [
            '<link rel="stylesheet" href="assets/foo-5UjPuW-k.css" />',
            '<link rel="stylesheet" href="assets/shared-ChJ_j-JJ.css" />'
        ];

        $result = $manifest->renderStyles('views/foo.js');
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<link rel="stylesheet" href="assets/shared-ChJ_j-JJ.css" />'
        ];

        $result = $manifest->renderStyles('views/bar.js');
        $this->assertSame(implode('', $expected), $result);
    }

    public function testPreloadTags(): void
    {
        $manifest = new ViteManifest($this->manifestFile);

        // views/foo.js
        $expected = [
            '<link rel="modulepreload" href="assets/shared-B7PI925R.js" />'
        ];

        $result = $manifest->renderPreloads('views/foo.js');
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<link rel="modulepreload" href="assets/shared-B7PI925R.js" />'
        ];

        $result = $manifest->renderPreloads('views/bar.js');
        $this->assertSame(implode('', $expected), $result);
    }
}
