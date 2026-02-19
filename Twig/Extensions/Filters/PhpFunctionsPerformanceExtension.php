<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Twig\Extensions\Filters;

use OxidEsales\Eshop\Core\Registry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PhpFunctionsPerformanceExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [
            new TwigFilter('parse_url', 'parse_url'),
            new TwigFilter('oxNew', 'oxNew'),
            new TwigFilter('strtotime', 'strtotime'),
            new TwigFilter('is_array', 'is_array'),
            new TwigFilter('urlencode', 'urlencode'),
            new TwigFilter('addslashes', 'addslashes'),
            new TwigFilter('getimagesize', [$this, 'optimizedGetImageSize']),
        ];
    }

    public function optimizedGetImageSize(?string $source)
    {

        if (!$source) {
            return false;
        }

        $source = trim($source);

        if (preg_match('#^https?://#i', $source)) {
            $config = Registry::getConfig();

            $shopUrl = rtrim(
                (string) ($config->getSslShopUrl() ?: $config->getShopUrl() ?: ''),
                '/'
            );

            if ($shopUrl !== '' && stripos($source, $shopUrl) === 0) {
                $pathPart = parse_url($source, PHP_URL_PATH) ?: '';

                $localPath = $this->buildShopPath($pathPart);
                if ($localPath !== null && is_file($localPath)) {
                    return @getimagesize($localPath);
                }
            }

            return @getimagesize($source);
        }

        if (str_starts_with($source, '/out/') || str_starts_with($source, 'out/')) {
            $localPath = $this->buildShopPath($source);

            if ($localPath !== null && is_file($localPath)) {
                return @getimagesize($localPath);
            }
        }

        $localPath = $this->buildShopPath($source);
        if ($localPath !== null && is_file($localPath)) {
            return @getimagesize($localPath);
        }

        return @getimagesize($source);
    }

    private function buildShopPath(string $relative): ?string
    {
        $config = Registry::getConfig();

        $shopDir = (string) ($config->getConfigParam('sShopDir') ?? '');
        if ($shopDir === '') {
            $shopDir = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        }

        $shopDir = rtrim($shopDir, DIRECTORY_SEPARATOR);
        if ($shopDir === '') {
            return null;
        }

        $relative = ltrim($relative, '/\\');
        return $shopDir . DIRECTORY_SEPARATOR . $relative;
    }
}
