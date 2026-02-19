<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Resolver\TemplateChain;

use OxidEsales\Twig\Resolver\TemplateChain\TemplateType\DataObject\TemplateTypeInterface;
use OxidEsales\Twig\Resolver\TemplateChain\TemplateType\TemplateTypeFactoryInterface;

final class CachedTemplateTypeFactory implements TemplateTypeFactoryInterface
{
    /** @var array<string, TemplateTypeInterface> */
    private array $cache = [];

    public function __construct(
        private TemplateTypeFactoryInterface $inner,
    ) {
    }

    public function createFromTemplateName(string $templateName): TemplateTypeInterface
    {
        if (isset($this->cache[$templateName])) {
            return $this->cache[$templateName];
        }

        return $this->cache[$templateName] = $this->inner->createFromTemplateName($templateName);
    }
}
