<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Extensions;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeWidgetLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

/**
 * Caches HTML output of static widgets (nocookie: 1).
 *
 * Widgets like oxwLanguageList, oxwCurrencyList, oxwServiceMenu, oxwCategoryTree
 * produce identical output for all visitors. Each include_widget call creates a
 * full sub-request through WidgetControl->start — caching avoids this overhead.
 *
 * Cache key: widget class + all params + language + shop ID.
 * Invalidated on oe:cache:clear (deletes source/tmp/).
 */
class CachedIncludeWidgetLogic extends IncludeWidgetLogic
{
    /** @var array<string, string> Per-request memory cache */
    private static array $memoryCache = [];

    private IncludeWidgetLogic $inner;
    private ModuleSettingServiceInterface $moduleSettingService;
    private ContextInterface $context;
    private string $cacheDir;
    private ?bool $enabled = null;

    public function __construct(
        IncludeWidgetLogic $inner,
        ModuleSettingServiceInterface $moduleSettingService,
        ContextInterface $context,
    ) {
        $this->inner = $inner;
        $this->moduleSettingService = $moduleSettingService;
        $this->context = $context;
        $this->cacheDir = rtrim(Registry::getConfig()->getConfigParam('sCompileDir'), DIRECTORY_SEPARATOR) . '/widget_cache';
    }

public function renderWidget(array $params)
    {
        if (empty($params['nocookie']) || !$this->isEnabled()) {
            return $this->inner->renderWidget($params);
        }

        $cacheKey = $this->buildCacheKey($params);

        if (isset(self::$memoryCache[$cacheKey])) {
            echo self::$memoryCache[$cacheKey];
            return;
        }

        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.html';

        if (file_exists($cacheFile)) {
            $html = file_get_contents($cacheFile);
            self::$memoryCache[$cacheKey] = $html;
            echo $html;
            return;
        }

        ob_start();
        $this->inner->renderWidget($params);
        $html = ob_get_clean();

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        file_put_contents($cacheFile, $html);
        self::$memoryCache[$cacheKey] = $html;

        echo $html;
    }

    private function isEnabled(): bool
    {
        if ($this->enabled === null) {
            try {
                $this->enabled = $this->moduleSettingService->getBoolean(
                    'cacheWidgetOutput',
                    'oxs_module_performance'
                );
            } catch (\Throwable) {
                $this->enabled = true;
            }
        }
        return $this->enabled;
    }

    private function buildCacheKey(array $params): string
    {
        $langId = Registry::getLang()->getBaseLanguage();
        $shopId = $this->context->getCurrentShopId();

        ksort($params);

        return md5(serialize($params) . '|' . $langId . '|' . $shopId);
    }
}
