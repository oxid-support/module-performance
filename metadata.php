<?php

$sMetadataVersion = "2.1";

$aModule = [
    "id" => "oxs_module_performance",
    "title" => "OXS :: Module Performance",
    "description" => [
        "de" => "<p>Beschleunigt den OXID eShop durch persistente Caches für Modul-Konfigurationen, "
              . "Modul-Metadaten, Template-Chains, Widget-HTML.</p>"
              . "<p><b>Warmup-Befehl:</b> <code>./vendor/bin/oe-console oxs:perf:warmup</code></p>"
              . "<ul>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup</code> — baut alle Caches neu auf</li>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup --modules</code> — Modul-Konfiguration (YAML), Modul-Metadaten (Pfade, Controller, Class-Extensions) und Modul-Settings (Einzelwerte)</li>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup --templates</code> — Template-Map (Pfad-Zuordnungen), Template-Chain (Vererbungskette) und leert den Widget-Cache (HTML-Fragmente)</li>"
              . "</ul>"
              . "<p>Nach Modul-Aktivierung/-Deaktivierung sollte der Warmup-Befehl ausgeführt werden, "
              . "um die Caches sauber neu aufzubauen.</p>",
        "en" => "<p>Speeds up OXID eShop with persistent caches for module configurations, "
              . "module metadata, template chains, widget HTML.</p>"
              . "<p><b>Warmup command:</b> <code>./vendor/bin/oe-console oxs:perf:warmup</code></p>"
              . "<ul>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup</code> — rebuilds all caches</li>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup --modules</code> — module configuration (YAML), module metadata (paths, controllers, class extensions) and module settings (individual values)</li>"
              . "<li><code>./vendor/bin/oe-console oxs:perf:warmup --templates</code> — template map (path mappings), template chain (inheritance) and clears the widget cache (HTML fragments)</li>"
              . "</ul>"
              . "<p>After module activation/deactivation the warmup command should be run "
              . "to cleanly rebuild the caches.</p>",
    ],
    "version" => "1.0.0",
    "author" => "OXID Support",
    "controllers" => [],
    "extend" => [
        \OxidEsales\Eshop\Core\ViewConfig::class => \OxidSupport\ModulePerformance\Core\ViewConfig::class,
    ],
    "settings" => [
        ['group' => 'oxs_perf_modules',   'name' => 'cacheModuleConfiguration', 'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_modules',   'name' => 'cacheModuleSettings',      'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_modules',   'name' => 'cacheModuleMetadata',      'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'cacheTemplateChain',       'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'cacheTemplateDirectories', 'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'cacheTemplateTypes',       'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'cacheTemplateMap',         'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'cacheWidgetOutput',        'type' => 'bool', 'value' => true],
        ['group' => 'oxs_perf_templates', 'name' => 'memoizeViewConfig',        'type' => 'bool', 'value' => true],
    ],
];
