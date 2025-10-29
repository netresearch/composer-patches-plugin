# Copilot Instructions for composer-patches-plugin

## Project Overview

This is a Composer plugin that allows you to provide patches for any package from any package. The plugin enables distributed patching with Composer, allowing users to define patches in their composer.json or in separate metapackages.

## Technology Stack

- **Language**: PHP 8.0+
- **Framework**: Composer Plugin API 2.0+
- **Testing**: PHPUnit (versions 9.5, 10.0, or 11.0)
- **Code Quality**: PHP CS Fixer (v3.0+)

## Project Structure

```
src/Netresearch/Composer/Patches/
├── Plugin.php              # Main plugin class
├── Installer.php           # Handles patch installation
├── Patch.php               # Patch model
├── PatchSet.php            # Collection of patches
├── Exception.php           # Base exception
├── PatchCommandException.php
└── Downloader/
    ├── DownloaderInterface.php
    └── Composer.php        # Downloads patches via Composer

tests/Netresearch/Test/Composer/Patches/
├── PluginTest.php
└── Downloader/
    └── ComposerTest.php
```

## Build and Test Instructions

### Install Dependencies
```bash
composer install --prefer-dist --no-progress
```

### Run Tests
```bash
composer test              # Run PHPUnit tests
composer test-compatibility # Run compatibility tests
```

### Code Quality
```bash
composer cs-fix           # Fix code style issues
composer cs-check         # Check code style (dry-run)
```

### Linting
PHP CS Fixer is configured in `.php-cs-fixer.php` with PSR-12 standards and custom rules.

## Coding Standards

- **PSR-12**: Follow PSR-12 coding style
- **PHP Version**: Target PHP 8.0+ with compatibility for 8.0, 8.1, 8.2, 8.3, and 8.4
- **Namespacing**: Use PSR-4 autoloading with `Netresearch\Composer\Patches` namespace
- **Type Declarations**: Use strict types and type hints where possible
- **Comments**: Add PHPDoc blocks for classes and methods
- **Tests**: Write unit tests for new functionality in the `tests/` directory

## Key Concepts

1. **Patches**: The plugin supports patches from URLs or local files
2. **Patch Properties**: 
   - `url` (required): URL or path to the patch
   - `title` (optional): Display title
   - `args` (optional): Additional arguments for patch command
   - `sha1` (optional): SHA1 checksum for security verification

3. **Patch Application**: Patches are applied with `-p1` option in the target package directory

## Common Development Tasks

### Adding New Features
- Update the relevant classes in `src/Netresearch/Composer/Patches/`
- Add corresponding unit tests in `tests/`
- Ensure code passes CS checks and tests
- Update README.md if user-facing changes are made

### Fixing Bugs
- Identify the affected class(es)
- Add a failing test first (TDD approach)
- Implement the fix
- Verify all tests pass

### Updating Dependencies
- Use Composer to update dependencies
- Run full test suite to ensure compatibility
- Check security with `composer audit`

## CI/CD

The project uses GitHub Actions for continuous integration:
- **Test Matrix**: PHP 8.0, 8.1, 8.2, 8.3, 8.4
- **Code Quality**: PHP CS Fixer checks
- **Security**: Composer audit and Symfony security checker
- **Coverage**: Code coverage on PHP 8.0 (uploaded to Codecov)

## Important Files

- `composer.json`: Project metadata and dependencies
- `phpunit.xml`: PHPUnit configuration
- `.php-cs-fixer.php`: PHP CS Fixer configuration
- `.github/workflows/ci.yml`: CI/CD pipeline
- `README.md`: User documentation

## Development Guidelines

1. **Make minimal changes**: Only modify what's necessary to fix the issue or add the feature
2. **Follow existing patterns**: Match the code style and structure already in the project
3. **Test thoroughly**: Ensure all existing tests pass and add new tests for changes
4. **Document changes**: Update relevant documentation (README, PHPDoc, etc.)
5. **Security first**: Validate all external inputs, especially patch URLs and checksums

## When Making Changes

- Run `composer cs-fix` before committing to ensure code style compliance
- Run `composer test` to verify all tests pass
- Check that changes work with all supported PHP versions (CI will verify this)
- Update documentation if adding new features or changing behavior
- Keep commits focused and well-described
