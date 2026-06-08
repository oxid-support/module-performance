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
 * full sub-request through WidgetControl->start, caching avoids this overhead.
 *
 * In addition to the captured HTML, side effects on Config::globalParameters
 * (script and style registrations the inner widget render performs) are
 * snapshot/diffed and replayed on cache hit, so the final
 * {{ script() }} / {{ style() }} at the end of the page still produces the
 * correct <script>/<link> tags.
 *
 * Cache key: widget class + all params + language + shop ID.
 * Cache files: source/tmp/widget_cache/<hash>.php.
 * Invalidated on oe:cache:clear (deletes source/tmp/).
 */
class CachedIncludeWidgetLogic extends IncludeWidgetLogic
{
    private const TRACKED_RESOURCE_KEYS = [
        'scripts',
        'scripts_dynamic',
        'includes',
        'includes_dynamic',
        'styles',
        'styles_dynamic',
        'conditional_styles',
        'conditional_styles_dynamic',
    ];

    private const PRIORITY_KEYED_KEYS = ['includes', 'includes_dynamic'];
    private const ASSOCIATIVE_KEYS = ['conditional_styles', 'conditional_styles_dynamic'];

    /** @var array<string, array{html: string, resources: array<string, array>}> */
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
            $entry = self::$memoryCache[$cacheKey];
            $this->replayResources($entry['resources']);
            echo $entry['html'];
            return;
        }

        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.php';

        if (file_exists($cacheFile)) {
            $entry = @include $cacheFile;
            if (is_array($entry) && isset($entry['html'], $entry['resources']) && is_array($entry['resources'])) {
                self::$memoryCache[$cacheKey] = $entry;
                $this->replayResources($entry['resources']);
                echo $entry['html'];
                return;
            }
        }

        $before = $this->snapshotResources();
        ob_start();
        $this->inner->renderWidget($params);
        $html = (string) ob_get_clean();
        $diff = $this->diffResources($before, $this->snapshotResources());

        // Dynamic resources may carry per-request state (CSRF tokens etc.),
        // never share them across requests.
        if ($this->containsDynamic($diff)) {
            echo $html;
            return;
        }

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        $entry = ['html' => $html, 'resources' => $diff];
        self::$memoryCache[$cacheKey] = $entry;
        $this->writeCacheAtomic($cacheFile, $entry);

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

    /**
     * @return array<string, array>
     */
    private function snapshotResources(): array
    {
        $config = Registry::getConfig();
        $snapshot = [];
        foreach (self::TRACKED_RESOURCE_KEYS as $key) {
            $snapshot[$key] = (array) $config->getGlobalParameter($key);
        }
        return $snapshot;
    }

    /**
     * @param array<string, array> $before
     * @param array<string, array> $after
     * @return array<string, array>
     */
    private function diffResources(array $before, array $after): array
    {
        $diff = [];
        foreach (self::TRACKED_RESOURCE_KEYS as $key) {
            $d = $this->diffForKey($key, $before[$key] ?? [], $after[$key] ?? []);
            if ($d !== []) {
                $diff[$key] = $d;
            }
        }
        return $diff;
    }

    private function diffForKey(string $key, array $before, array $after): array
    {
        if (in_array($key, self::PRIORITY_KEYED_KEYS, true)) {
            $result = [];
            foreach ($after as $priority => $files) {
                $existing = isset($before[$priority]) ? (array) $before[$priority] : [];
                $new = array_values(array_diff((array) $files, $existing));
                if ($new !== []) {
                    $result[$priority] = $new;
                }
            }
            return $result;
        }

        if (in_array($key, self::ASSOCIATIVE_KEYS, true)) {
            return array_diff_assoc($after, $before);
        }

        return array_values(array_diff($after, $before));
    }

    private function containsDynamic(array $diff): bool
    {
        foreach ($diff as $key => $values) {
            if ($values !== [] && str_ends_with($key, '_dynamic')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, array> $diff
     */
    private function replayResources(array $diff): void
    {
        if ($diff === []) {
            return;
        }
        $config = Registry::getConfig();
        foreach ($diff as $key => $values) {
            if ($values === []) {
                continue;
            }
            $existing = (array) $config->getGlobalParameter($key);
            $config->setGlobalParameter($key, $this->mergeForKey($key, $existing, $values));
        }
    }

    private function mergeForKey(string $key, array $existing, array $diff): array
    {
        if (in_array($key, self::PRIORITY_KEYED_KEYS, true)) {
            foreach ($diff as $priority => $files) {
                $current = isset($existing[$priority]) ? (array) $existing[$priority] : [];
                $existing[$priority] = array_values(array_unique(array_merge($current, $files)));
            }
            return $existing;
        }

        if (in_array($key, self::ASSOCIATIVE_KEYS, true)) {
            // current-request entries win over cached on URL collision
            return $existing + $diff;
        }

        return array_values(array_unique(array_merge($existing, $diff)));
    }

    private function writeCacheAtomic(string $file, array $entry): void
    {
        $tmp = $file . '.' . bin2hex(random_bytes(4));
        $payload = '<?php return ' . var_export($entry, true) . ';' . PHP_EOL;
        if (file_put_contents($tmp, $payload) === false) {
            return;
        }
        if (!rename($tmp, $file)) {
            @unlink($tmp);
        }
    }
}
