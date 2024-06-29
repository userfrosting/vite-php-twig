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

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use UserFrosting\ViteTwig\ViteManifest;
use UserFrosting\ViteTwig\ViteTwigExtension;

/**
 * Adds Vite related function to Twig.
 *
 * Added functions :
 * - vite_js(string $entryName)
 * - vite_css(string $entryName)
 *
 * @see https://vitejs.dev/guide/backend-integration
 */
class ViteTwigExtensionTest extends TestCase
{
    protected Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();

        $manifest = new ViteManifest(
            __DIR__ . '/manifests/manifest.json'
        );
        $extension = new ViteTwigExtension($manifest);

        // Create dumb Twig and test adding extension
        $loader = new FilesystemLoader();
        $this->twig = new Environment($loader);
        $this->twig->addExtension($extension);
    }

    public function testScriptTags(): void
    {
        // views/foo.js
        $expected = [
            '<script type="module" src="assets/foo-BRBmoGS9.js"></script>'
        ];

        $result = $this->twig->createTemplate("{{ vite_js('views/foo.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<script type="module" src="assets/bar-gkvgaI9m.js"></script>'
        ];

        $result = $this->twig->createTemplate("{{ vite_js('views/bar.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);

        // Both together
        $expected = [
            '<script type="module" src="assets/foo-BRBmoGS9.js"></script>',
            '<script type="module" src="assets/bar-gkvgaI9m.js"></script>'
        ];

        $result = $this->twig->createTemplate("{{ vite_js('views/foo.js', 'views/bar.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);
    }

    public function testLinkTags(): void
    {
        // views/foo.js
        $expected = [
            '<link rel="stylesheet" href="assets/foo-5UjPuW-k.css" />',
            '<link rel="stylesheet" href="assets/shared-ChJ_j-JJ.css" />'
        ];

        $result = $this->twig->createTemplate("{{ vite_css('views/foo.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<link rel="stylesheet" href="assets/shared-ChJ_j-JJ.css" />'
        ];

        $result = $this->twig->createTemplate("{{ vite_css('views/bar.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);
    }

    public function testPreloadTags(): void
    {
        // views/foo.js
        $expected = [
            '<link rel="modulepreload" href="assets/shared-B7PI925R.js" />'
        ];

        $result = $this->twig->createTemplate("{{ vite_preload('views/foo.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);

        // views/bar.js
        $expected = [
            '<link rel="modulepreload" href="assets/shared-B7PI925R.js" />'
        ];

        $result = $this->twig->createTemplate("{{ vite_preload('views/bar.js') }}")->render();
        $this->assertSame(implode('', $expected), $result);
    }

    public function testDevServer(): void
    {
        $manifest = new ViteManifest(
            manifestPath: __DIR__ . '/manifests/manifest.json',
            devEnabled: true,
        );
        $extension = new ViteTwigExtension($manifest);

        // Create dumb Twig and test adding extension
        $loader = new FilesystemLoader();
        $twig = new Environment($loader);
        $twig->addExtension($extension);

        // views/bar.js
        $this->assertSame(implode([
            '<script type="module" src="@vite/client"></script>',
            '<script type="module" src="views/bar.js"></script>'
        ]), $twig->createTemplate("{{ vite_js('views/bar.js') }}")->render());

        // Both
        $this->assertSame(implode([
            '<script type="module" src="@vite/client"></script>',
            '<script type="module" src="views/foo.js"></script>',
            '<script type="module" src="views/bar.js"></script>',
        ]), $twig->createTemplate("{{ vite_js('views/foo.js', 'views/bar.js') }}")->render());

        // No styles or imports
        $this->assertSame('', $twig->createTemplate("{{ vite_css('views/foo.js') }}")->render());
        $this->assertSame('', $twig->createTemplate("{{ vite_preload('views/bar.js') }}")->render());
    }
}
