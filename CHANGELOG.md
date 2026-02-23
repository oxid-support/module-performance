# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-02-23

### Fixed
- Removed hardcoded Docker paths in FilesystemModuleConfigurationCache and FilesystemLoader; all cache paths now derived dynamically from sCompileDir
- Fixed DI container circular reference caused by LoggerInterface injection in FilesystemModuleConfigurationCache and PersistentTemplateChainResolver; logger is now lazy-loaded via ContainerFactory
- Fixed template discovery in warmup command returning 0 templates due to missing parent::__construct() call in FilesystemLoader
- Fixed double-slash in cache file paths when sCompileDir ends with a trailing separator
- Use ContextInterface::getCurrentShopId() consistently instead of Registry::getConfig()->getShopId()

## [1.0.0] - 2026-02-20
Initial release.
