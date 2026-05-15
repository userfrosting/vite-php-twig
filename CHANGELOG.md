# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.2.0
- Fix `getStyle()` silently dropping the standalone `.css` output file when a chunk also has a `css[]` array (assignment was overwriting instead of merging)
- Add `renderStylePreloads()` method to `ViteManifest` and `ViteManifestInterface` to emit `<link rel="preload" as="style">` hints for CSS files
- Add `vite_css_preload()` Twig function backed by `renderStylePreloads()`

## 1.1.0
- Support CSS standalone file by [@imagoiq](https://github.com/imagoiq) in [#1](https://github.com/userfrosting/vite-php-twig/pull/1)
- Add PHP 8.4 Tests

## 1.0.2
- Apply `basePath` to server URL

## 1.0.1
- `ViteTwigExtension` constructor uses interface instead of class

## 1.0.0
- Initial version