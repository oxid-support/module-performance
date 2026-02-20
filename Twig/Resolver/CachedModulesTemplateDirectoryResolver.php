<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Resolver;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\Twig\Resolver\TemplateDirectoryResolverInterface;

final class CachedModulesTemplateDirectoryResolver implements TemplateDirectoryResolverInterface
{
    private ?array $cachedDirectories = null;
    private ?bool $enabled = null;

    public function __construct(
        private TemplateDirectoryResolverInterface $inner,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function getTemplateDirectories(): array
    {
        if (!$this->isEnabled()) {
            return $this->inner->getTemplateDirectories();
        }

        if ($this->cachedDirectories !== null) {
            return $this->cachedDirectories;
        }

        return $this->cachedDirectories = $this->inner->getTemplateDirectories();
    }

    private function isEnabled(): bool
    {
        if ($this->enabled === null) {
            try {
                $this->enabled = $this->moduleSettingService->getBoolean(
                    'cacheTemplateDirectories',
                    'oxs_module_performance'
                );
            } catch (\Throwable) {
                $this->enabled = true;
            }
        }
        return $this->enabled;
    }
}
