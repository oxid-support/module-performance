<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Resolver;

use OxidEsales\Twig\Resolver\TemplateDirectoryResolverInterface;

final class CachedModulesTemplateDirectoryResolver implements TemplateDirectoryResolverInterface
{
    private ?array $cachedDirectories = null;

    public function __construct(
        private TemplateDirectoryResolverInterface $inner,
    ) {
    }

    public function getTemplateDirectories(): array
    {
        if ($this->cachedDirectories !== null) {
            return $this->cachedDirectories;
        }

        return $this->cachedDirectories = $this->inner->getTemplateDirectories();
    }
}
