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

/**
 * @see https://vitejs.dev/guide/backend-integration
 */
interface ViteManifestInterface
{
    /**
     * Fetches the script files from the manifest for the specified entries
     * and returns them as HTML script tags. If the dev server is used, the vite
     * client for hot reloading is instead injected with reference to the entries.
     *
     * @param string ...$entries
     *
     * @return string The script tags
     */
    public function renderScripts(string ...$entries): string;

    /**
     * Fetches the style files from the manifest for the specified entries and
     * returns them as HTML link tags. If the dev server is used, no link tags
     * are returned, since they are injected by vite at runtime.
     *
     * @param string ...$entries
     *
     * @return string The script tags
     */
    public function renderStyles(string ...$entries): string;

    /**
     * Fetches the script imports from the manifest for the specified entries
     * and returns them as HTML module preloads. If the dev server is used, no
     * preloading tags are returned, since they are not required for development.
     *
     * @param string ...$entries
     *
     * @return string The script preload tags
     */
    public function renderPreloads(string ...$entries): string;

    /**
     * Fetches and returns all script files for the specified entry points, or
     * the server config if development server is activated.
     *
     * @param string ...$entries
     *
     * @return string[] Path to each scripts files
     */
    public function getScripts(string ...$entries): array;

    /**
     * Fetches and returns all style files for the specified entry points,
     * unless dev server is enabled, in which case no style are required.
     *
     * @param string ...$entries
     *
     * @return string[] Path to each style files
     */
    public function getStyles(string ...$entries): array;

    /**
     * Fetches and returns all import files for the specified entry points,
     * to make them usable in preloading tags, unless dev server is enabled,
     * in which case no style are required.
     *
     * @param string ...$entries
     *
     * @return string[] Path to each scripts preload files
     */
    public function getImports(string ...$entries): array;
}
