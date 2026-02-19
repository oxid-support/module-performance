<?php

declare(strict_types=1);

namespace OxidSupport\ModulePerformance\Module\Cache;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\Twig\Resolver\TemplateChain\TemplateChainResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Rebuilds the persistent caches managed by oxid-support/module-performance.
 *
 * Usage:
 *   vendor/bin/oe-console oxs:perf:warmup               # all caches (default)
 *   vendor/bin/oe-console oxs:perf:warmup --module-settings
 *   vendor/bin/oe-console oxs:perf:warmup --template-chain
 *
 * Run this after module activation/deactivation or settings changes.
 * The command runs in its own process — no interference with the DI
 * container rebuild that happens during module (de)activation.
 */
class WarmupModuleConfigurationCacheCommand extends Command
{
    public function __construct(
        private ModuleConfigurationDaoInterface $dao,
        private TemplateChainResolverInterface $chainResolver,
        private FilesystemLoader $loader,
        private ContextInterface $context,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('oxs:perf:warmup');
        $this->setDescription('Rebuild persistent caches for oxid-support/module-performance');
        $this->addOption('module-settings', null, InputOption::VALUE_NONE, 'Warm up module configuration YAML cache only');
        $this->addOption('template-chain', null, InputOption::VALUE_NONE, 'Warm up template chain cache only');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleSettings = $input->getOption('module-settings');
        $templateChain = $input->getOption('template-chain');

        // No specific option = warm up everything
        $all = !$moduleSettings && !$templateChain;

        if ($all || $moduleSettings) {
            $this->warmupModuleSettings($output);
        }

        if ($all || $templateChain) {
            $this->warmupTemplateChain($output);
        }

        return Command::SUCCESS;
    }

    /**
     * Reads all module YAML configurations via the DAO, populating
     * FilesystemModuleConfigurationCache. At shutdown, persist() writes to disk.
     */
    private function warmupModuleSettings(OutputInterface $output): void
    {
        $shopId = $this->context->getCurrentShopId();
        $configs = $this->dao->getAll($shopId);

        $output->writeln(sprintf(
            'Module settings: cached <info>%d</info> configurations',
            count($configs)
        ));
    }

    /**
     * Resolves template chains for all known templates, populating
     * PersistentTemplateChainResolver. At shutdown, persist() writes to disk.
     */
    private function warmupTemplateChain(OutputInterface $output): void
    {
        $templates = $this->discoverTemplates();
        $resolved = 0;

        foreach ($templates as $templateName) {
            try {
                $this->chainResolver->getLastChild($templateName);
                $resolved++;
            } catch (\Throwable) {
                // Template may not be resolvable (e.g. CMS-only templates)
            }
        }

        $output->writeln(sprintf(
            'Template chain: resolved <info>%d</info> of %d templates',
            $resolved,
            count($templates)
        ));
    }

    /**
     * Discovers all .html.twig templates from the filesystem loader's namespaces.
     *
     * Scans each registered namespace directory for .html.twig files and
     * converts them to Twig template names (e.g. @apex/page/shop/start.html.twig).
     *
     * @return string[]
     */
    private function discoverTemplates(): array
    {
        $templates = [];

        foreach ($this->loader->getNamespaces() as $namespace) {
            if ($namespace === FilesystemLoader::MAIN_NAMESPACE) {
                continue;
            }

            foreach ($this->loader->getPaths($namespace) as $path) {
                if (!is_dir($path)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && str_ends_with($file->getFilename(), '.html.twig')) {
                        $relative = ltrim(substr($file->getPathname(), strlen($path)), '/');
                        $templates[] = '@' . $namespace . '/' . $relative;
                    }
                }
            }
        }

        return array_unique($templates);
    }
}
