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

| Cache | Beschreibung | Toggle |
|---|---|---|
| **Configuration** | Serialisiert alle YAML-Modul-Konfigurationen in eine Cache-Datei. Eliminiert ~600ms YAML-Parsing pro Request bei 30 Modulen. | immer aktiv |
| **Settings** | Per-Setting-Cache für Modul-Konfigurationswerte. Invalidiert beim Speichern und dispatcht `SettingChangedEvent`. | immer aktiv |
| **Metadata** | Cached aktive Modul-Pfade, Controller und Class-Extensions über ModuleCacheService. | `cacheModuleMetadata` |

### Templates

| Cache | Beschreibung | Toggle |
|---|---|---|
| **Chain** | Persistiert Chain-Auflösung (lastChild, parent, hasParent) über ModuleCacheService. Eliminiert Iteration über alle Module x Templates. | `cacheTemplateChain` |
| **Directories** | In-Memory-Cache für `getTemplateDirectories()`, das mehrfach pro Request aufgerufen wird. | `cacheTemplateDirectories` |
| **Types** | In-Memory-Cache für `createFromTemplateName()`, das für jedes Template aufgerufen wird. | `cacheTemplateTypes` |
| **Map** | Vorberechnete Map aller Template-Pfade als PHP-Datei. Eliminiert wiederholtes Verzeichnis-Scanning. | `cacheTemplateMap` |
| **Widget-HTML** | Cached den HTML-Output statischer Widgets (nocookie) als Dateien. Umgeht den vollständigen Sub-Request pro Widget. | `cacheWidgetOutput` |
| **ViewConfig** | Memoized `getViewThemeParam()`-Aufrufe innerhalb eines Requests. Verhindert redundante DB-Abfragen für Theme-Parameter. | `memoizeViewConfig` |
| **Twig-Filter** | Stellt PHP-Funktionen (`parse_url`, `oxNew`, `strtotime`, `is_array`, `urlencode`, `addslashes`) als native Twig-Filter bereit. Optimiertes `getimagesize` nutzt Dateisystem statt Netzwerkzugriff. | — |

## Admin-Einstellungen

Alle Caches sind über den Admin unter **Erweiterungen → Module → Module Performance → Einstellungen** konfigurierbar. Die Einstellungen sind in zwei Gruppen aufgeteilt:

- **Module** — Modul-Konfiguration (immer aktiv), Modul-Settings (immer aktiv), Modul-Metadaten
- **Templates** — Template-Chain, Verzeichnisse, Typen, Map, Widget-HTML, ViewConfig

Die beiden Kern-Caches (Konfiguration und Settings) sind immer aktiv und können nicht deaktiviert werden.

## Commands

| Command | Description |
|---|---|
| `./vendor/bin/oe-console oxs:perf:warmup` | Alle Caches neu aufbauen |
| `./vendor/bin/oe-console oxs:perf:warmup --modules` | Modul-Konfiguration (YAML), Metadaten (Pfade, Controller, Class-Extensions) und Settings (Einzelwerte) |
| `./vendor/bin/oe-console oxs:perf:warmup --templates` | Template-Map, Template-Chain und leert den Widget-Cache |

Für Multishop-Setups `--shop-id` angeben:

    ./vendor/bin/oe-console oxs:perf:warmup --shop-id=2

## Cache Invalidation

Automatisch bei:
- Modul-Aktivierung/-Deaktivierung (EventSubscriber)
- Modul-Setting-Änderungen (triggert `ModuleConfigurationChangedEvent`)
- `oe:cache:clear` (löscht Cache-Verzeichnis inkl. Modul-Dateien)

Nach Invalidierung wird die Cache-Datei gelöscht und im selben Request nicht neu erstellt. Der nächste reguläre Request baut den Cache automatisch auf, aber nur für die tatsächlich genutzten Module. `oxs:perf:warmup` baut alle Caches auf einmal neu auf und vermeidet den langsameren ersten Request.

## Benchmark (30 Module)

| Request | Ohne Modul | Mit Modul + Warmup |
|---|---|---|
| Cold | ~4.8s | ~1.3s |
| Warm | ~1.1s | ~0.7s |
