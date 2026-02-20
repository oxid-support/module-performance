<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Resolver\TemplateChain;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\Twig\Resolver\TemplateChain\TemplateType\DataObject\TemplateTypeInterface;
use OxidEsales\Twig\Resolver\TemplateChain\TemplateType\TemplateTypeFactoryInterface;

final class CachedTemplateTypeFactory implements TemplateTypeFactoryInterface
{
    /** @var array<string, TemplateTypeInterface> */
    private array $cache = [];
    private ?bool $enabled = null;

    public function __construct(
        private TemplateTypeFactoryInterface $inner,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function createFromTemplateName(string $templateName): TemplateTypeInterface
    {
        if (!$this->isEnabled()) {
            return $this->inner->createFromTemplateName($templateName);
        }

        if (isset($this->cache[$templateName])) {
            return $this->cache[$templateName];
        }

        return $this->cache[$templateName] = $this->inner->createFromTemplateName($templateName);
    }

    private function isEnabled(): bool
    {
        if ($this->enabled === null) {
            try {
                $this->enabled = $this->moduleSettingService->getBoolean(
                    'cacheTemplateTypes',
                    'oxs_module_performance'
                );
            } catch (\Throwable) {
                $this->enabled = true;
            }
        }
        return $this->enabled;
    }
}
