<?php

namespace Netresearch\Test\Composer\Patches;

use Netresearch\Composer\Patches\Plugin;
use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallationManager;

/**
 * Tests for the main Plugin class.
 */
class PluginTest extends TestCase
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

    protected function setUp(): void
    {
        $this->plugin = new Plugin();
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
    }

    /**
     * Test that the plugin implements the required interfaces.
     */
    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(PluginInterface::class, $this->plugin);
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->plugin);
    }

    /**
     * Test plugin activation.
     */
    public function testActivation(): void
    {
        $config = $this->createMock(Config::class);
        $installationManager = $this->createMock(InstallationManager::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->composer->expects($this->once())
            ->method('getInstallationManager')
            ->willReturn($installationManager);

        $installationManager->expects($this->once())
            ->method('addInstaller')
            ->with($this->isInstanceOf(\Netresearch\Composer\Patches\Installer::class));

        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * Test getSubscribedEvents returns the expected events.
     */
    public function testGetSubscribedEvents(): void
    {
        $events = Plugin::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey('pre-package-uninstall', $events);
        $this->assertArrayHasKey('pre-package-update', $events);
        $this->assertArrayHasKey('post-update-cmd', $events);
        $this->assertArrayHasKey('post-install-cmd', $events);

        // Check that the methods are correctly mapped
        $this->assertEquals(['restore'], $events['pre-package-uninstall']);
        $this->assertEquals(['restore'], $events['pre-package-update']);
        $this->assertEquals(['apply'], $events['post-update-cmd']);
        $this->assertEquals(['apply'], $events['post-install-cmd']);
    }

    /**
     * Test deactivate method exists.
     */
    public function testDeactivateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->plugin, 'deactivate'));

        // Should not throw any exceptions
        $this->plugin->deactivate($this->composer, $this->io);
    }

    /**
     * Test uninstall method exists.
     */
    public function testUninstallMethodExists(): void
    {
        $this->assertTrue(method_exists($this->plugin, 'uninstall'));

        // Should not throw any exceptions
        $this->plugin->uninstall($this->composer, $this->io);
    }
}
