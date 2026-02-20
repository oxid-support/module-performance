<?php

$aLang = [
    'charset' => 'UTF-8',

    'SHOP_MODULE_GROUP_oxs_perf_modules'   => 'Module',
    'SHOP_MODULE_GROUP_oxs_perf_templates' => 'Templates',

    // --- Module ---

    'SHOP_MODULE_cacheModuleConfiguration' => 'Modul-Konfiguration cachen (immer aktiv)',
    'HELP_SHOP_MODULE_cacheModuleConfiguration' => 'Speichert die geparsten YAML-Modul-Konfigurationen als serialisierte Datei auf der Festplatte. Dadurch entfällt das wiederholte Einlesen und Parsen der YAML-Dateien bei jedem Request. Dieser Cache ist immer aktiv und kann nicht deaktiviert werden, da er die Grundlage für alle anderen Caches bildet.<script>var c=document.querySelector(\'input[type=checkbox][name="confbools[cacheModuleConfiguration]"]\');if(c){c.checked=true;c.disabled=true;}</script>',

    'SHOP_MODULE_cacheModuleSettings' => 'Modul-Settings cachen (immer aktiv)',
    'HELP_SHOP_MODULE_cacheModuleSettings' => 'Speichert individuelle Modul-Einstellungen über den ModuleCacheService, sodass sie nicht bei jedem Zugriff aus der YAML-Konfiguration gelesen werden müssen. Wird bei Änderungen über den Admin automatisch invalidiert. Dieser Cache ist immer aktiv und kann nicht deaktiviert werden, da ohne ihn das gesamte Modul keinen Nutzen hätte.<script>var c=document.querySelector(\'input[type=checkbox][name="confbools[cacheModuleSettings]"]\');if(c){c.checked=true;c.disabled=true;}</script>',

    'SHOP_MODULE_cacheModuleMetadata' => 'Modul-Metadaten cachen',
    'HELP_SHOP_MODULE_cacheModuleMetadata' => 'Speichert Modul-Pfade, Controller-Zuordnungen und Class-Extensions über den ModuleCacheService. Vermeidet das wiederholte Auslesen dieser Informationen aus der Modul-Konfiguration. Wird bei Modul-Aktivierung/-Deaktivierung automatisch invalidiert.',

    // --- Templates ---

    'SHOP_MODULE_cacheTemplateChain' => 'Template-Chain cachen',
    'HELP_SHOP_MODULE_cacheTemplateChain' => 'Speichert die aufgelöste Template-Vererbungskette (Parent/Child) persistent über Requests hinweg. Ohne diesen Cache muss die Chain bei jedem Seitenaufruf erneut berechnet werden. Wird automatisch invalidiert, wenn sich die Modul-Konfiguration ändert.',

    'SHOP_MODULE_cacheTemplateDirectories' => 'Template-Verzeichnisse cachen',
    'HELP_SHOP_MODULE_cacheTemplateDirectories' => 'Merkt sich die ermittelten Template-Verzeichnisse aller Module innerhalb eines Requests. Verhindert wiederholte Dateisystem-Abfragen für dieselben Verzeichnispfade. Besonders effektiv bei Shops mit vielen aktiven Modulen.',

    'SHOP_MODULE_cacheTemplateTypes' => 'Template-Typen cachen',
    'HELP_SHOP_MODULE_cacheTemplateTypes' => 'Speichert die erzeugten TemplateType-Objekte im Arbeitsspeicher innerhalb eines Requests. Vermeidet die wiederholte Instanziierung identischer Objekte bei der Template-Auflösung. Reduziert CPU- und Speicherverbrauch bei komplexen Template-Strukturen.',

    'SHOP_MODULE_cacheTemplateMap' => 'Template-Map cachen',
    'HELP_SHOP_MODULE_cacheTemplateMap' => 'Erstellt eine vorberechnete Map aller Template-Pfade als PHP-Datei. Dadurch entfällt das wiederholte Scannen der Verzeichnisse durch den Twig-Loader. Die Map wird beim ersten Aufruf automatisch erzeugt und bei Änderungen über den Warmup-Befehl aktualisiert.',

    'SHOP_MODULE_cacheWidgetOutput' => 'Widget-HTML cachen',
    'HELP_SHOP_MODULE_cacheWidgetOutput' => 'Speichert den HTML-Output von statischen Widgets (nocookie-Widgets) als Dateien im tmp-Verzeichnis. Jeder Widget-Aufruf erzeugt normalerweise einen vollständigen Sub-Request — der Cache umgeht diesen Overhead. Der Cache wird über den Warmup-Befehl oder oe:cache:clear geleert.',

    'SHOP_MODULE_memoizeViewConfig' => 'ViewConfig-Aufrufe memoizen',
    'HELP_SHOP_MODULE_memoizeViewConfig' => 'Speichert die Ergebnisse von getViewThemeParam()-Aufrufen im Arbeitsspeicher innerhalb eines Requests. Templates rufen Theme-Parameter häufig mehrfach auf — diese Optimierung verhindert redundante Datenbank-Abfragen. Besonders wirksam bei Templates mit vielen Theme-Parametern.',
];
