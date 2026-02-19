# Module Performance

Persistent caches for OXID eShop module configuration and Twig template chain resolution.
Reduces TTFB with 30 active modules from ~4.8s to ~0.7s (after warmup).

## Installation

    composer config repositories.oxs-module-performance vcs https://github.com/oxid-support/module-performance
    composer require oxid-support/module-performance

    ./vendor/bin/oe-console oe:module:activate oxs_module_performance
    ./vendor/bin/oe-console oxs:perf:warmup

## What gets cached

### 1. Module Configuration
Serializes all YAML module configurations into a single cache file.
Eliminates ~600ms of YAML parsing per request with 30 modules.

File: `var/cache/modules/{shopId}/oxs_perf_module_configurations`

### 2. Template Chain
Persists chain resolution results (lastChild, parent, hasParent)
via ModuleCacheService. Eliminates iterating over all modules x templates.

Files: `var/cache/modules/{shopId}/oxs_perf_lastchild.txt` etc.

Additionally, two in-memory decorators prevent redundant computation within a single request:

- **CachedModulesTemplateDirectoryResolver** — `getTemplateDirectories()` is called multiple times per request. The decorator stores the result and returns it directly on subsequent calls.
- **CachedTemplateTypeFactory** — `createFromTemplateName()` is called for every template. Instead of recalculating each time, the result is cached per template name.

## Commands

| Command | Description |
|---|---|
| `oxs:perf:warmup` | Warm up all caches (default) |
| `oxs:perf:warmup --module-settings` | YAML configuration cache only |
| `oxs:perf:warmup --template-chain` | Template chain cache only |

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
