<?php

namespace Netresearch\Test\Composer\Patches;

use Netresearch\Composer\Patches\Plugin;
use Netresearch\Composer\Patches\Exception;
use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\InstallationManager;

/**
 * Tests for the Plugin restore functionality with missing patch files.
 */
class PluginRestoreTest extends TestCase
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Composer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $composer;

    /**
     * @var IOInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $io;

    /**
     * @var RepositoryManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repositoryManager;

    /**
     * @var InstalledRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localRepository;

    /**
     * @var InstallationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $installationManager;

    protected function setUp(): void
    {
        $this->plugin = new Plugin();
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
        $this->repositoryManager = $this->createMock(RepositoryManager::class);
        $this->localRepository = $this->createMock(InstalledRepositoryInterface::class);
        $this->installationManager = $this->createMock(InstallationManager::class);

        $config = $this->createMock(Config::class);

        $this->composer->method('getConfig')->willReturn($config);
        $this->composer->method('getRepositoryManager')->willReturn($this->repositoryManager);
        $this->composer->method('getInstallationManager')->willReturn($this->installationManager);

        $this->repositoryManager->method('getLocalRepository')->willReturn($this->localRepository);

        // Activate the plugin
        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * Test that restore works normally when there are no patches.
     */
    public function testRestoreWorksNormallyWithNoPatches(): void
    {
        // Create a mock package with no patches
        $initialPackage = $this->createMock(PackageInterface::class);
        $targetPackage = $this->createMock(PackageInterface::class);

        $initialPackage->method('getName')->willReturn('vendor/package');
        $initialPackage->method('getVersion')->willReturn('1.0.0');
        $initialPackage->method('getExtra')->willReturn([]);

        $targetPackage->method('getName')->willReturn('vendor/package');
        $targetPackage->method('getVersion')->willReturn('2.0.0');

        // Mock the update operation
        $operation = $this->createMock(UpdateOperation::class);
        $operation->method('getInitialPackage')->willReturn($initialPackage);
        $operation->method('getTargetPackage')->willReturn($targetPackage);

        $event = $this->createMock(PackageEvent::class);
        $event->method('getOperation')->willReturn($operation);

        // Mock local repository to return no packages
        $this->localRepository->method('getPackages')->willReturn([]);

        // No warnings should be written for packages without patches
        // This should work normally without any output
        $this->plugin->restore($event);
        
        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    /**
     * Test that restore works for uninstall operations.
     */
    public function testRestoreWorksForUninstall(): void
    {
        // Create a mock package
        $package = $this->createMock(PackageInterface::class);

        $package->method('getName')->willReturn('vendor/package');
        $package->method('getVersion')->willReturn('1.0.0');
        $package->method('getExtra')->willReturn([]);

        // Mock the uninstall operation
        $operation = $this->createMock(UninstallOperation::class);
        $operation->method('getPackage')->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->method('getOperation')->willReturn($operation);

        // Mock local repository to return no packages
        $this->localRepository->method('getPackages')->willReturn([]);

        // This should work normally
        $this->plugin->restore($event);
        
        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }
}
