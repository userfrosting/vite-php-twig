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

use JsonException;
use UserFrosting\ViteTwig\Exceptions\EntrypointNotFoundException;
use UserFrosting\ViteTwig\Exceptions\ManifestNotFoundException;

/**
 * @see https://vitejs.dev/guide/backend-integration
 */
class ViteManifest implements ViteManifestInterface
{
    /**
     * Locally cached version of the manifest. The content of the manifest is
     * saved in this object to reduce the number of times the file is read.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $manifestContent = null;

    /**
     * @param string $manifestPath The path to the manifest file. Can be omitted
     *                             if dev server is enabled.
     * @param string $basePath     Public base path from which Vite's published
     *                             assets are served. The assets paths will be
     *                             relative to the `outDir` in your vite
     *                             configuration. It could also point to a CDN
     *                             or other asset server, if you are serving
     *                             assets from a different domain.
     * @param string $serverUrl    The vite server url, including port.
     * @param bool   $devEnabled   Indicates whether the application is running
     *                             in development mode (i.e. using vite server).
     *                             Defaults to false.
     */
    public function __construct(
        protected string $manifestPath = '',
        protected string $basePath = '',
        protected string $serverUrl = '',
        protected bool $devEnabled = false,
    ) {
    }

    /**
     * Fetches the script files from the manifest for the specified entries
     * and returns them as HTML script tags. If the dev server is used, the vite
     * client for hot reloading is instead injected with reference to the entries.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the
     *                                     manifest.
     *
     * @return string The script tags
     */
    public function renderScripts(string ...$entries): string
    {
        $scripts = $this->getScripts(...$entries);

        $tags = array_map(function (string $file) {
            return $this->renderScript($file);
        }, $scripts);

        return implode('', $tags);
    }

    /**
     * Fetches the style files from the manifest for the specified entries and
     * returns them as HTML link tags. If the dev server is used, no link tags
     * are returned, since they are injected by vite at runtime.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the
     *                                     manifest.
     *
     * @return string The script tags
     */
    public function renderStyles(string ...$entries): string
    {
        $styles = $this->getStyles(...$entries);

        $tags = array_map(function (string $file) {
            return $this->renderStylesheet($file);
        }, $styles);

        return implode('', $tags);
    }

    /**
     * Fetches the script imports from the manifest for the specified entries
     * and returns them as HTML module preloads. If the dev server is used, no
     * preloading tags are returned, since they are not required for development.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the
     *                                     manifest.
     *
     * @return string The script preload tags
     */
    public function renderPreloads(string ...$entries): string
    {
        $imports = $this->getImports(...$entries);

        $tags = array_map(function (string $file) {
            return $this->renderPreload($file);
        }, $imports);

        return implode('', $tags);
    }

    /**
     * Fetches and returns all script files for the specified entry points, or
     * the server config if development server is activated.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the manifest.
     *
     * @return string[] Path to each scripts files
     */
    public function getScripts(string ...$entries): array
    {
        // Server. Return entries + server;
        if ($this->useServer()) {
            $scripts = array_merge(['@vite/client'], $entries);

            return $this->prefixFiles($scripts);
        }

        // Default behavior
        $files = [];
        foreach ($entries as $entry) {
            $files = array_merge($files, $this->getScript($entry));
        }

        // Apply prefix & return
        return $this->prefixFiles(array_unique($files));
    }

    /**
     * Fetches and returns all style files for the specified entry points,
     * unless dev server is enabled, in which case only standalone entries are returned.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the manifest.
     *
     * @return string[] Path to each style files
     */
    public function getStyles(string ...$entries): array
    {
        // Server. Return standalone entries;
        if ($this->useServer()) {
            $stylesheets = array_merge($entries);

            return $this->prefixFiles($stylesheets);
        }

        // Default behavior
        $files = [];
        foreach ($entries as $entry) {
            $files = array_merge($files, $this->getStyle($entry));
        }

        // Apply prefix & return
        return $this->prefixFiles(array_unique($files));
    }

    /**
     * Fetches and returns all import files for the specified entry points,
     * to make them usable in preloading tags, unless dev server is enabled,
     * in which case no style are required.
     *
     * @param string ...$entries
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If an entry point is not found in the manifest.
     *
     * @return string[] Path to each scripts preload files
     */
    public function getImports(string ...$entries): array
    {
        // Server. Returns nothing.
        if ($this->useServer()) {
            return [];
        }

        // Default behavior
        $files = [];
        foreach ($entries as $entry) {
            $files = array_merge($files, $this->getImport($entry));
        }

        // Apply prefix & return
        return $this->prefixFiles(array_unique($files));
    }

    /**
     * Return all script files for a specific entry point in the manifest, not
     * taking into consideration the development server.
     *
     * "Returns the file key of the entry point chunk"
     *
     * @param string $entry
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If entry point is not found in the
     *                                     manifest.
     *
     * @return string[] Script files for $entry. Empty array if entry point
     *                  doesn't have files.
     */
    protected function getScript(string $entry): array
    {
        $manifest = $this->parseManifest();
        $chunk = $this->getEntrypoint($entry, $manifest);

        if (!array_key_exists('file', $chunk)) {
            return [];
        }

        return [
            $chunk['file'],
        ];
    }

    /**
     * Return all style files for a specific entry point in the manifest, not
     * taking into consideration the development server.
     *
     * "[Return] each file in the entry point chunk's css list, recursively
     * follow all chunks in the entry point's imports list and [return] each CSS
     * file of each imported chunk."
     *
     * @param string $entry
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If entry point is not found in the
     *                                     manifest.
     *
     * @return string[] Script files for $entry. Empty array if entry point
     *                  doesn't have files.
     */
    protected function getStyle(string $entry): array
    {
        $manifest = $this->parseManifest();
        $chunk = $this->getEntrypoint($entry, $manifest);

        // Return container
        $files = [];

        // Entry point's output is a standalone css file
        if (str_ends_with($chunk['file'], '.css')) {
            $files[] = $chunk['file'];
        }

        // Add entry point chunk's css list
        if (array_key_exists('css', $chunk)) {
            $files = $chunk['css'];
        }

        // Recursively follow all chunks in the entry point's imports list
        if (array_key_exists('imports', $chunk)) {
            foreach ($chunk['imports'] as $importName) {
                try {
                    $importFiles = $this->getStyle($importName);
                    $files = array_merge($files, $importFiles);
                } catch (EntrypointNotFoundException $e) {
                    continue;
                }
            }
        }

        return $files;
    }

    /**
     * Return all script files for a specific entry point in the manifest, not
     * taking into consideration the development server.
     *
     * "Returns the file key of the entry point chunk"
     *
     * @param string $entry
     *
     * @throws JsonException               If manifest can't be read
     * @throws EntrypointNotFoundException If entry point is not found in the
     *                                     manifest.
     *
     * @return string[] Script files for $entry. Empty array if entry point
     *                  doesn't have files.
     */
    protected function getImport(string $entry): array
    {
        $manifest = $this->parseManifest();
        $chunk = $this->getEntrypoint($entry, $manifest);

        // Return container
        $files = [];

        if (array_key_exists('imports', $chunk)) {
            foreach ($chunk['imports'] as $importName) {
                try {
                    $importFiles = $this->getScript($importName);
                    $files = array_merge($files, $importFiles);
                } catch (EntrypointNotFoundException $e) {
                    continue;
                }
            }
        }

        return $files;
    }

    /**
     * Returns the entry pint chunk.
     *
     * @param string  $entryName
     * @param mixed[] $manifest
     *
     * @throws EntrypointNotFoundException If entrypoint is not found in manifest.
     *
     * @return mixed[]
     */
    protected function getEntrypoint(string $entryName, array $manifest): array
    {
        if (!array_key_exists($entryName, $manifest)) {
            $message = sprintf('Entry `%s` not found in manifest', $entryName);

            throw new EntrypointNotFoundException($message);
        }

        return $manifest[$entryName];
    }

    /**
     * Parses and returns the manifest.json file, if it exists.
     *
     * @throws JsonException If manifest can't be read
     *
     * @return array<string, mixed>
     */
    protected function parseManifest(): array
    {
        // Return cached manifest if it exist
        if ($this->manifestContent !== null) {
            return $this->manifestContent;
        }

        // Throw exception if manifest is not found
        if (!file_exists($this->manifestPath)) {
            throw new ManifestNotFoundException(
                sprintf('Manifest `%s` not found.', $this->manifestPath)
            );
        }

        // Read manifest file
        $contents = file_get_contents($this->manifestPath);
        $manifest = json_decode(
            strval($contents),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Cache manifest content and return.
        return $this->manifestContent = $manifest;
    }

    /**
     * Return script tag for specified filename.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function renderScript(string $fileName): string
    {
        return sprintf(
            '<script type="module" src="%s"></script>',
            $fileName
        );
    }

    /**
     * Return style tag for specified filename.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function renderStylesheet(string $fileName): string
    {
        return sprintf(
            '<link rel="stylesheet" href="%s" />',
            $fileName
        );
    }

    /**
     * Return script preload tag for specified filename.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function renderPreload(string $fileName): string
    {
        return sprintf(
            '<link rel="modulepreload" href="%s" />',
            $fileName
        );
    }

    /**
     * Prefixes a file name with either the dev server URL or the base path,
     * depending on whether the dev server is used or not.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function prefixFile(string $fileName): string
    {
        $prefix = $this->useServer()
            ? $this->serverUrl . $this->basePath
            : $this->basePath;

        return sprintf('%s%s', $prefix, $fileName);
    }

    /**
     * Same as prefixFile, but accept and array of files.
     *
     * @param string[] $files
     *
     * @return string[]
     */
    protected function prefixFiles(array $files): array
    {
        return array_map(function (string $file) {
            return $this->prefixFile($file);
        }, $files);
    }

    /**
     * @return bool True if the dev server is enabled, otherwise false.
     */
    protected function useServer(): bool
    {
        return $this->devEnabled;
    }
}
