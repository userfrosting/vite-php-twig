<?php

declare(strict_types=1);

/*
 * Vite Twig Functions
 *
 * @link      https://github.com/userfrosting/vite-php-twig
 * @copyright Copyright (c) 2024 Louis Charette, UserFrosting
 * @license   https://github.com/userfrosting/vite-php-twig/blob/main/LICENSE.md (MIT License)
 */

namespace UserFrosting\ViteTwig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Adds Vite related function to Twig.
 *
 * Added functions :
 * - vite_js(string $entryName)
 * - vite_css(string $entryName)
 * - vite_preload(string $entryName)
 *
 * @see https://vitejs.dev/guide/backend-integration
 */
final class ViteTwigExtension extends AbstractExtension
{
    /**
     * @param ViteManifestInterface $manifest
     */
    public function __construct(
        private ViteManifestInterface $manifest
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_js', [$this->manifest, 'renderScripts'], ['is_safe' => ['html']]),
            new TwigFunction('vite_css', [$this->manifest, 'renderStyles'], ['is_safe' => ['html']]),
            new TwigFunction('vite_preload', [$this->manifest, 'renderPreloads'], ['is_safe' => ['html']]),
        ];
    }
}
