<?php
namespace Netresearch\Composer\Patches;

/*                                                                        *
 * This script belongs to the Composer-TYPO3-Installer package            *
 * (c) 2014 Netresearch GmbH & Co. KG                                     *
 * This copyright notice MUST APPEAR in all copies of the script!         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

use Composer\Script\Event;
use Composer\Script\PackageEvent;
use Composer\Script\ScriptEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Package\PackageInterface;

/**
 * The patchSet integration for Composer, which applies the patches contained
 * with this package onto other packages
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Plugin implements PluginInterface, EventSubscriberInterface {
	/**
	 * The name of this package
	 * @todo Eventually retrieve this from the composer.json of this package
	 */
	const SELF_PACKAGE_NAME = 'netresearch/composer-patches-plugin';


	/**
	 * @var IOInterface
	 */
	protected $io;

	/**
	 * @var Composer
	 */
	protected $composer;

	/**
	 * Restored packages are registered here in order to not doubly restore 'em
	 * @var array
	 */
	protected $restoredPackages = array();

	/**
	 * The packages that should be patched (collected on update/install and
	 * patched after all packages are updated/installed)
	 * @var array
	 */
	protected $packagesToPatch = array();

	/**
	 *
	 * @var Downloader\Interface
	 */
	protected $downloader;

	/**
	 * Activate the plugin (called from {@see \Composer\Plugin\PluginManager})
	 *
	 * @param \Composer\Composer $composer
	 * @param \Composer\IO\IOInterface $io
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$this->io = $io;
		$this->composer = $composer;
		$this->downloader = new Downloader\Composer($io);

		// Add the installer
		$noopInstaller = new Installer();
		$composer->getInstallationManager()->addInstaller($noopInstaller);
	}

	/**
	 * Get the events, this {@see \Composer\EventDispatcher\EventSubscriberInterface}
	 * subscribes to
	 *
	 * Returns the events only once (on consecutive calls this will return an empty
	 * array) as composer creates a copy of the current class, when it already exists
	 * which happens after an update of the patchSet package
	 * @see \Composer\Plugin\PluginManager::registerPackage()
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		// Composer creates a copy of the current class, when it already exists
		// This happens after an update of the patchSet package
		// @see \Composer\Plugin\PluginManager::registerPackage()
		$key = 'T3eeComposerPatchSetPlugin';
		if (isset($GLOBALS[$key])) {
			return array();
		}
		$GLOBALS[$key] = 1;

		return array(
			ScriptEvents::PRE_PACKAGE_UNINSTALL => array('restore'),
			ScriptEvents::PRE_PACKAGE_UPDATE => array('restore'),
			ScriptEvents::POST_PACKAGE_UPDATE => array('collect'),
			ScriptEvents::POST_PACKAGE_INSTALL => array('collect'),
			ScriptEvents::POST_UPDATE_CMD => array('apply'),
			ScriptEvents::POST_INSTALL_CMD => array('apply')
		);
	}

	/**
	 * Event handler for preUpdate/preUninstall events: Revert the patches
	 *
	 * @param \Composer\Script\PackageEvent $event
	 * @throws Exception
	 */
	public function restore(PackageEvent $event) {
		$operation = $event->getOperation();

		if ($operation instanceof UninstallOperation) {
			$initialPackage = $operation->getPackage();
		} elseif ($operation instanceof UpdateOperation) {
			$initialPackage = $operation->getInitialPackage();
		} else {
			throw new Exception('Unknown operation ' . get_class($operation));
		}

		foreach ($this->getPatches($initialPackage, $this->restoredPackages) as $patchesAndPackage) {
			list($patches, $package) = $patchesAndPackage;
			$packagePath = $this->getPackagePath($package);
			foreach (array_reverse($patches) as $patch) {
				/* @var $patch Patch */
				$this->writePatchNotice('revert', $patch, $package);
				$patch->revert($packagePath);
			}
		}
	}

	/**
	 * Event handler to the postUpdate/postInstall events: Collect the packages
	 * as potential canditates for patching
	 *
	 * @param \Composer\Script\PackageEvent $event
	 * @throws Exception
	 */
	public function collect(PackageEvent $event) {
		$operation = $event->getOperation();
		if ($operation instanceof InstallOperation) {
			$package = $operation->getPackage();
		} elseif ($operation instanceof UpdateOperation) {
			$package = $operation->getTargetPackage();
		} else {
			throw new Exception('Unknown operation ' . get_class($operation));
		}

		$this->packagesToPatch[$package->getName()] = $package;
	}

	/**
	 * Event handler to the postUpdateCmd/postInstallCmd events: Look for patches
	 * for the patch candidate packages and apply them when available
	 *
	 * @param \Composer\Script\Event $event
	 * @param Downloader\DownloaderInterface|NULL $downloader
	 */
	public function apply(Event $event) {
		$history = array();
		$appliedPatches = array();

		foreach ($this->packagesToPatch as $initialPackage) {
			foreach ($this->getPatches($initialPackage, $history) as $patchesAndPackage) {
				list($patches, $package) = $patchesAndPackage;
				$packagePath = $this->getPackagePath($package);
				foreach ($patches as $patch) {
					$this->writePatchNotice('test', $patch, $package);
					if (!$patch->test($packagePath)) {
						$this->io->write('  <warning>Failing patch detected - reverting already applied patches</warning>');
						foreach (array_reverse($appliedPatches) as $patchPackageAndPath) {
							list($revertPatch, $revertPackage, $revertPath) = $patchPackageAndPath;
							$this->writePatchNotice('revert', $revertPatch, $revertPackage);
							$revertPatch->revert($revertPath);
						}
						throw $patch->getException();
					}
					$this->writePatchNotice('apply', $patch, $package);
					$patch->apply($packagePath);
					$appliedPatches[] = array($patch, $package, $packagePath);
				}
			}
		}
	}

	/**
	 * Get the patches and packages that are not already in $history
	 * 
	 * @param \Composer\Package\PackageInterface $initialPackage
	 * @param array &$history
	 * @return array
	 */
	protected function getPatches(PackageInterface $initialPackage, array &$history) {
		$packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
		$patchSets = array();
		foreach ($packages as $package) {
			$extra = $package->getExtra();
			if (isset($extra['patches']) && $initialPackage->getName() != $package->getName()) {
				$patchSets[$package->getName()] = array($extra['patches'], array($initialPackage));
			}
		}

		$extra = $initialPackage->getExtra();
		if (isset($extra['patches'])) {
			$patchSets[$initialPackage->getName()] = array($extra['patches'], $packages);
		}

		// Patches may also be defined in the root package.
		$rootPackage = $this->composer->getPackage();
		$extra = $rootPackage->getExtra();
		if (!empty($extra['patches'])) {
		  foreach (array_keys($extra['patches']) as $packageName) {
		      $packages = $this->composer->getRepositoryManager()->getLocalRepository()->findPackages($packageName);
		      $patchSets[$rootPackage->getName()] = array($extra['patches'], $packages);
		  }
		}

		$patchesAndPackages = array();
		foreach ($patchSets as $sourceName => $patchConfAndPackages) {
			$patchSet = new PatchSet($patchConfAndPackages[0], $this->downloader);
			foreach ($patchConfAndPackages[1] as $package) {
				$id = $sourceName . '->' . $package->getName();
				$patches = $patchSet->getPatches($package->getName(), $package->getVersion());
				if (!array_key_exists($id, $history) && count($patches)) {
					$patchesAndPackages[$id] = array($patches, $package);
					$history[$id] = TRUE;
				}
			}
		}

		return $patchesAndPackages;
	}

	/**
	 * Get the install path for a package
	 *
	 * @param \Composer\Package\PackageInterface $package
	 * @return string
	 */
	protected function getPackagePath(PackageInterface $package) {
		return $this->composer->getInstallationManager()->getInstallPath($package);
	}

	/**
	 * Write a notice to IO
	 *
	 * @param string $action
	 * @param \Netresearch\Composer\Patches\Patch $patch
	 * @param \Composer\Package\PackageInterface $package
	 */
	protected function writePatchNotice($action, Patch $patch, PackageInterface $package) {
		$adverbMap = array('test' => 'on', 'apply' => 'to', 'revert' => 'from');
		if ($action == 'test' && !$this->io->isVeryVerbose()) {
			return;
		}
		if ($action == 'revert' && !$this->io->isVerbose()) {
			return;
		}
		$msg = '  - ' . ucfirst($action) . 'ing patch';
		if ($this->io->isVerbose()) {
			$msg .= ' <info>' . $patch->id . '</info>';
		}
		$msg .=	' ' . $adverbMap[$action];
		$msg .=	' <info>' . $package->getName() . '</info>';
		if ($this->io->isVerbose()) {
			' (<comment>' . $package->getPrettyVersion() . '</comment>)';
		}
		if (isset($patch->title)) {
			$msg .= ': <comment>' . $patch->title . '</comment>';
		}
		$this->io->write($msg);
	}
}
?>
