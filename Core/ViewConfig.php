<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Core;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;

class ViewConfig extends ViewConfig_parent
{
    /** @var array<string, mixed> */
    private static array $themeParamCache = [];
    private static ?bool $memoizeEnabled = null;

    public function getViewThemeParam($sName)
    {
        if (!$this->isMemoizeEnabled()) {
            return parent::getViewThemeParam($sName);
        }

        if (!array_key_exists($sName, self::$themeParamCache)) {
            self::$themeParamCache[$sName] = parent::getViewThemeParam($sName);
        }

        return self::$themeParamCache[$sName];
    }

    private function isMemoizeEnabled(): bool
    {
        if (self::$memoizeEnabled === null) {
            try {
                self::$memoizeEnabled = ContainerFactory::getInstance()
                    ->getContainer()
                    ->get(ModuleSettingServiceInterface::class)
                    ->getBoolean('memoizeViewConfig', 'oxs_module_performance');
            } catch (\Throwable) {
                self::$memoizeEnabled = true;
            }
        }
        return self::$memoizeEnabled;
    }
}
