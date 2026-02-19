# Module Performance

Persistent caches for OXID eShop module configuration and Twig template chain resolution.
Reduces TTFB with 30 active modules from ~4.8s to ~0.7s (after warmup).

## Installation

    composer config repositories.oxs-module-performance vcs https://github.com/oxid-support/module-performance
    composer require oxid-support/module-performance

    ./vendor/bin/oe-console oe:module:activate oxs_module_performance
    ./vendor/bin/oe-console oxs:perf:warmup

## What gets cached

### Modules
- **Configuration** — Serializes all YAML module configurations into a single cache file. Eliminates ~600ms of YAML parsing per request with 30 modules. File: `var/cache/modules/{shopId}/oxs_perf_module_configurations`
- **Metadata** — Caches active module paths, controllers and class extensions via ModuleCacheService. Avoids re-resolving module metadata on every request.
- **Settings** — Per-setting cache for module configuration values. Invalidates on save and dispatches `SettingChangedEvent`.

### Templates
- **Chain** — Persists chain resolution results (lastChild, parent, hasParent) via ModuleCacheService. Eliminates iterating over all modules x templates. Files: `var/cache/modules/{shopId}/oxs_perf_lastchild.txt` etc.
- **Map** — Pre-computed map of all template paths, eliminating repeated directory scanning. Built automatically on first request. File: `source/tmp/template_map_shop_1.php`
- **Directory Resolver** — In-memory cache for `getTemplateDirectories()`, which is called multiple times per request.
- **Type Factory** — In-memory cache for `createFromTemplateName()`, which is called for every template.
- **Twig Filters** — Exposes PHP functions (`parse_url`, `oxNew`, `strtotime`, `is_array`, `urlencode`, `addslashes`) as native Twig filters. Includes an optimized `getimagesize` that detects local files and uses filesystem instead of network access.

## Commands

| Command | Description |
|---|---|
| `oxs:perf:warmup` | Warm up all caches (default) |
| `oxs:perf:warmup --modules` | Module caches only (configuration, metadata, settings) |
| `oxs:perf:warmup --templates` | Template caches only (chain, map) |

For multishop setups, pass `--shop-id` to warm up a specific sub-shop:

    ./vendor/bin/oe-console oxs:perf:warmup --shop-id=2

## Cache Invalidation

Automatic on:
- Module activation/deactivation (EventSubscriber)
- Module setting changes (triggers `ModuleConfigurationChangedEvent`)
- `oe:cache:clear` (deletes cache directory including module files)

After invalidation the cache file is deleted and will not be recreated during the
same request. The next regular request rebuilds the cache automatically, but only
for the modules it actually uses. Running `oxs:perf:warmup` rebuilds all caches
at once and avoids the slower first request.

## Benchmark (30 modules)

| Request | Without module | With module + warmup |
|---|---|---|
| Cold | ~4.8s | ~1.3s |
| Warm | ~1.1s | ~0.7s |