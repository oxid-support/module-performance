<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Resolver\TemplateChain;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Cache\ModuleCacheServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\Twig\Resolver\TemplateChain\TemplateChainResolverInterface;
use Psr\Log\LoggerInterface;

final class PersistentTemplateChainResolver implements TemplateChainResolverInterface
{
    private const KEY_LASTCHILD = 'oxs_perf_lastchild';
    private const KEY_PARENT = 'oxs_perf_parent';
    private const KEY_HASPARENT = 'oxs_perf_hasparent';

    /** @var array<string,string> */
    private array $lastChildCache = [];
    /** @var array<string,string> */
    private array $parentCache = [];
    /** @var array<string,bool> */
    private array $hasParentCache = [];

    private bool $lastChildLoaded = false;
    private bool $parentLoaded = false;
    private bool $hasParentLoaded = false;

    private bool $lastChildDirty = false;
    private bool $parentDirty = false;
    private bool $hasParentDirty = false;

    private bool $shutdownRegistered = false;

    private ?bool $enabled = null;

    public function __construct(
        private TemplateChainResolverInterface $inner,
        private ModuleCacheServiceInterface $moduleCacheService,
        private ContextInterface $context,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    private function getLogger(): ?LoggerInterface
    {
        try {
            return ContainerFactory::getInstance()->getContainer()->get(LoggerInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getLastChild(string $templateName): string
    {
        if (!$this->isEnabled()) {
            return $this->inner->getLastChild($templateName);
        }

        $this->loadLastChildCache();

        if (isset($this->lastChildCache[$templateName])) {
            return $this->lastChildCache[$templateName];
        }

        $result = $this->inner->getLastChild($templateName);
        $this->lastChildCache[$templateName] = $result;
        $this->lastChildDirty = true;
        $this->ensureShutdown();

        return $result;
    }

    public function getParent(string $templateName): string
    {
        if (!$this->isEnabled()) {
            return $this->inner->getParent($templateName);
        }

        $this->loadParentCache();

        if (isset($this->parentCache[$templateName])) {
            return $this->parentCache[$templateName];
        }

        $result = $this->inner->getParent($templateName);
        $this->parentCache[$templateName] = $result;
        $this->parentDirty = true;
        $this->ensureShutdown();

        return $result;
    }

    public function hasParent(string $templateName): bool
    {
        if (!$this->isEnabled()) {
            return $this->inner->hasParent($templateName);
        }

        $this->loadHasParentCache();

        if (array_key_exists($templateName, $this->hasParentCache)) {
            return $this->hasParentCache[$templateName];
        }

        $result = $this->inner->hasParent($templateName);
        $this->hasParentCache[$templateName] = $result;
        $this->hasParentDirty = true;
        $this->ensureShutdown();

        return $result;
    }

    private function getShopId(): int
    {
        return $this->context->getCurrentShopId();
    }

    private function loadLastChildCache(): void
    {
        if ($this->lastChildLoaded) {
            return;
        }
        $this->lastChildLoaded = true;

        try {
            $this->lastChildCache = $this->moduleCacheService->get(self::KEY_LASTCHILD, $this->getShopId());
        } catch (\Throwable $e) {
            $this->getLogger()?->warning('ModulePerformance: Failed to load lastChild cache', ['exception' => $e->getMessage()]);
        }
    }

    private function loadParentCache(): void
    {
        if ($this->parentLoaded) {
            return;
        }
        $this->parentLoaded = true;

        try {
            $this->parentCache = $this->moduleCacheService->get(self::KEY_PARENT, $this->getShopId());
        } catch (\Throwable $e) {
            $this->getLogger()?->warning('ModulePerformance: Failed to load parent cache', ['exception' => $e->getMessage()]);
        }
    }

    private function loadHasParentCache(): void
    {
        if ($this->hasParentLoaded) {
            return;
        }
        $this->hasParentLoaded = true;

        try {
            $this->hasParentCache = $this->moduleCacheService->get(self::KEY_HASPARENT, $this->getShopId());
        } catch (\Throwable $e) {
            $this->getLogger()?->warning('ModulePerformance: Failed to load hasParent cache', ['exception' => $e->getMessage()]);
        }
    }

    private function ensureShutdown(): void
    {
        if ($this->shutdownRegistered) {
            return;
        }
        $this->shutdownRegistered = true;

        register_shutdown_function(function (): void {
            $this->persist();
        });
    }

    private function isEnabled(): bool
    {
        if ($this->enabled === null) {
            try {
                $this->enabled = $this->moduleSettingService->getBoolean(
                    'cacheTemplateChain',
                    'oxs_module_performance'
                );
            } catch (\Throwable) {
                $this->enabled = true;
            }
        }
        return $this->enabled;
    }

    private function persist(): void
    {
        $shopId = $this->getShopId();

        try {
            if ($this->lastChildDirty) {
                $this->moduleCacheService->put(self::KEY_LASTCHILD, $shopId, $this->lastChildCache);
            }
            if ($this->parentDirty) {
                $this->moduleCacheService->put(self::KEY_PARENT, $shopId, $this->parentCache);
            }
            if ($this->hasParentDirty) {
                $this->moduleCacheService->put(self::KEY_HASPARENT, $shopId, $this->hasParentCache);
            }
        } catch (\Throwable $e) {
            $this->getLogger()?->error('ModulePerformance: Failed to persist cache', ['exception' => $e->getMessage()]);
        }
    }
}
