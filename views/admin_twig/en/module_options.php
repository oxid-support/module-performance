<?php

$aLang = [
    'charset' => 'UTF-8',

    'SHOP_MODULE_GROUP_oxs_perf_modules'   => 'Modules',
    'SHOP_MODULE_GROUP_oxs_perf_templates' => 'Templates',

    // --- Modules ---

    'SHOP_MODULE_cacheModuleConfiguration' => 'Cache module configuration (always active)',
    'HELP_SHOP_MODULE_cacheModuleConfiguration' => 'Stores parsed YAML module configurations as a serialized file on disk. This eliminates repeated reading and parsing of YAML files on every request. This cache is always active and cannot be disabled, as it forms the foundation for all other caches.<script>var c=document.querySelector(\'input[type=checkbox][name="confbools[cacheModuleConfiguration]"]\');if(c){c.checked=true;c.disabled=true;}</script>',

    'SHOP_MODULE_cacheModuleSettings' => 'Cache module settings (always active)',
    'HELP_SHOP_MODULE_cacheModuleSettings' => 'Stores individual module settings via the ModuleCacheService so they do not need to be read from the YAML configuration on every access. Automatically invalidated when settings are changed via the admin. This cache is always active and cannot be disabled, as the entire module would be pointless without it.<script>var c=document.querySelector(\'input[type=checkbox][name="confbools[cacheModuleSettings]"]\');if(c){c.checked=true;c.disabled=true;}</script>',

    'SHOP_MODULE_cacheModuleMetadata' => 'Cache module metadata',
    'HELP_SHOP_MODULE_cacheModuleMetadata' => 'Stores module paths, controller mappings, and class extensions via the ModuleCacheService. Avoids repeated reading of this information from the module configuration. Automatically invalidated on module activation/deactivation.',

    // --- Templates ---

    'SHOP_MODULE_cacheTemplateChain' => 'Cache template chain',
    'HELP_SHOP_MODULE_cacheTemplateChain' => 'Persists the resolved template inheritance chain (parent/child) across requests. Without this cache, the chain must be recalculated on every page load. Automatically invalidated when the module configuration changes.',

    'SHOP_MODULE_cacheTemplateDirectories' => 'Cache template directories',
    'HELP_SHOP_MODULE_cacheTemplateDirectories' => 'Memoizes the resolved template directories of all modules within a single request. Prevents repeated filesystem lookups for the same directory paths. Especially effective for shops with many active modules.',

    'SHOP_MODULE_cacheTemplateTypes' => 'Cache template types',
    'HELP_SHOP_MODULE_cacheTemplateTypes' => 'Stores created TemplateType objects in memory within a single request. Avoids repeated instantiation of identical objects during template resolution. Reduces CPU and memory usage with complex template structures.',

    'SHOP_MODULE_cacheTemplateMap' => 'Cache template map',
    'HELP_SHOP_MODULE_cacheTemplateMap' => 'Creates a precomputed map of all template paths as a PHP file. This eliminates repeated directory scanning by the Twig loader. The map is automatically generated on first access and updated via the warmup command.',

    'SHOP_MODULE_cacheWidgetOutput' => 'Cache widget HTML output',
    'HELP_SHOP_MODULE_cacheWidgetOutput' => 'Stores the HTML output of static widgets (nocookie widgets) as files in the tmp directory. Each widget call normally creates a full sub-request — the cache bypasses this overhead. The cache is cleared via the warmup command or oe:cache:clear.',

    'SHOP_MODULE_memoizeViewConfig' => 'Memoize ViewConfig calls',
    'HELP_SHOP_MODULE_memoizeViewConfig' => 'Caches the results of getViewThemeParam() calls in memory within a single request. Templates frequently call theme parameters multiple times — this optimization prevents redundant database queries. Especially effective for templates with many theme parameters.',
];
