<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Module\Cache;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Cache\ModuleConfigurationCacheInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Event\ModuleConfigurationChangedEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\FinalizingModuleActivationEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\FinalizingModuleDeactivationEvent;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Persistent filesystem cache for module configurations.
 *
 * Decorates the core ClassPropertyModuleConfigurationCache
 * with a serialized file that survives across requests. This eliminates repeated
 * YAML parsing in ModuleConfigurationDao.
 *
 * ## Lifecycle
 *
 * 1. First request (or after `oxs:perf:warmup`): loadFromFile() deserializes the file,
 *    populates the inner RAM cache. No YAML parsing.
 *
 * 2. Cache miss: falls through to the DAO which parses YAML and calls put().
 *    At shutdown, persist() writes updated data to disk.
 *
 * ## Invalidation
 *
 * Subscribes to module lifecycle events:
 *   - FinalizingModuleActivationEvent
 *   - FinalizingModuleDeactivationEvent
 *   - ModuleConfigurationChangedEvent
 *
 * Sets an `invalidated` flag that prevents persist() from recreating the file
 * at shutdown. This ensures the cache stays deleted until explicitly rebuilt.
 *
 * ## Warmup
 *
 * After module changes, run `oe-console oxs:perf:warmup` to rebuild the cache.
 * This runs in a clean process (no two-instance problem from DI rebuild)
 * and calls dao->getAll() to fill and persist the cache.
 *
 * ## File location
 *
 * /var/www/var/cache/modules/{shopId}/oxs_perf_module_configurations
 */
class FilesystemModuleConfigurationCache implements ModuleConfigurationCacheInterface, EventSubscriberInterface
{
    private const CACHE_KEY = 'oxs_perf_module_configurations';

    /** @var array<int, array<string, ModuleConfiguration>> */
    private array $memory = [];

    private bool $loaded = false;
    private bool $dirty = false;
    private bool $invalidated = false;

    public function __construct(
        private ModuleConfigurationCacheInterface $inner,
        private ContextInterface $context,
        private LoggerInterface $logger,
    ) {
        register_shutdown_function([$this, 'persist']);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FinalizingModuleActivationEvent::class => 'onModuleCacheInvalidated',
            FinalizingModuleDeactivationEvent::class => 'onModuleCacheInvalidated',
            ModuleConfigurationChangedEvent::class => 'onModuleCacheInvalidated',
        ];
    }

    /**
     * Resets state and prevents the shutdown persist from recreating the file.
     *
     * The `invalidated` flag blocks both persist() and put() for the remainder
     * of this process. This is necessary because during module (de)activation,
     * the DI container is rebuilt within the same process — a second cache
     * instance would otherwise persist stale data at shutdown.
     *
     * Run `oxs:perf:warmup` after module changes to rebuild the cache cleanly.
     */
    public function onModuleCacheInvalidated(): void
    {
        $this->memory = [];
        $this->loaded = false;
        $this->dirty = false;
        $this->invalidated = true;

        try {
            $cacheFile = $this->getCacheFilePath();
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('ModulePerformance: Failed to delete module configuration cache file', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function put(int $shopId, ModuleConfiguration $configuration): void
    {
        $this->inner->put($shopId, $configuration);

        if ($this->invalidated) {
            return;
        }

        $this->loadFromFile();
        $this->memory[$shopId][$configuration->getId()] = $configuration;
        $this->dirty = true;
    }

    public function get(string $moduleId, int $shopId): ModuleConfiguration
    {
        if ($this->inner->exists($moduleId, $shopId)) {
            return $this->inner->get($moduleId, $shopId);
        }

        $this->loadFromFile();

        if (isset($this->memory[$shopId][$moduleId])) {
            $this->inner->put($shopId, $this->memory[$shopId][$moduleId]);
            return $this->memory[$shopId][$moduleId];
        }

        throw new \RuntimeException("ModuleConfiguration not found: {$moduleId} / shop {$shopId}");
    }

    public function exists(string $moduleId, int $shopId): bool
    {
        if ($this->inner->exists($moduleId, $shopId)) {
            return true;
        }

        $this->loadFromFile();

        return isset($this->memory[$shopId][$moduleId]);
    }

    public function evict(string $moduleId, int $shopId): void
    {
        $this->inner->evict($moduleId, $shopId);

        $this->loadFromFile();

        if (isset($this->memory[$shopId][$moduleId])) {
            unset($this->memory[$shopId][$moduleId]);
            $this->dirty = true;
        }
    }

    /**
     * Persists the in-memory cache to disk (called via register_shutdown_function).
     *
     * Skips when:
     * - No data changed ($dirty = false)
     * - Cache was invalidated during this process ($invalidated = true)
     *
     * Uses atomic write (tmp + rename) to prevent corruption on concurrent access.
     */
    public function persist(): void
    {
        if (!$this->dirty || $this->invalidated) {
            return;
        }

        try {
            $cacheFile = $this->getCacheFilePath();
            $dir = dirname($cacheFile);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $data = serialize($this->memory);
            $tmp = $cacheFile . '.tmp.' . getmypid();
            file_put_contents($tmp, $data);
            rename($tmp, $cacheFile);

            $this->dirty = false;
        } catch (\Throwable $e) {
            $this->logger->error('ModulePerformance: Failed to persist module configuration cache', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function loadFromFile(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        try {
            $cacheFile = $this->getCacheFilePath();

            if (!file_exists($cacheFile)) {
                return;
            }

            $data = file_get_contents($cacheFile);

            if ($data === false || $data === '') {
                return;
            }

            $unserialized = unserialize($data);

            if (!is_array($unserialized)) {
                return;
            }

            $this->memory = $unserialized;

            foreach ($this->memory as $shopId => $modules) {
                foreach ($modules as $config) {
                    $this->inner->put($shopId, $config);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('ModulePerformance: Failed to load module configuration cache', [
                'exception' => $e->getMessage(),
            ]);
            $this->memory = [];
        }
    }

    private function getCacheFilePath(): string
    {
        $shopId = $this->context->getCurrentShopId();

        return '/var/www/var/cache/modules/' . $shopId . '/' . self::CACHE_KEY;
    }
}
