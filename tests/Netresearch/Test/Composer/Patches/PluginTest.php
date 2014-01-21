<?php
namespace Netresearch\Test\Composer\Patches;

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

use Netresearch\Composer\Patches\Plugin;
use Composer\Package\Package;
use Composer\Script\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Netresearch\Composer\Patches\Patch;

class PluginTest extends TestCase {
	/**
	 * The package to test
	 */
	const TEST_PACKAGE_NAME = 'vendor/package';

	/**
	 * Object with various paths
	 * @var \stdClass
	 */
	protected $paths;

	/**
	 * IO mock
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $io;

	/**
	 * List of patch IDs from fixture patchSet, expected to be applied/reverted
	 * @var array
	 */
	protected $expectedPatchIds;

	/**
	 * When true, downloadCallback will download the next-to-last patch as last
	 * which will cause a failing patch
	 * @var boolean
	 */
	protected $downloadFailingPatch = FALSE;

	/**
	 * SetUp
	 */
	public function setUp() {
		parent::setUp();
		$this->setPaths();
		$this->expectedPatchIds = $this->extractPatchIds($this->paths->patchSetSrc);
		$this->io = $this->getMock('Composer\IO\IOInterface');
	}

	/**
	 * TearDown
	 */
	public function tearDown() {
		parent::tearDown();
		if (file_exists($this->paths->tmp)) {
			$this->rmdir($this->paths->tmp);
		}
	}

	/**
	 * Set various paths (without creating them)
	 */
	protected function setPaths() {
		$paths = new \stdClass();
		$paths->tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'composer-patches-plugin-test';

		$paths->fixtures = __DIR__ . '/Fixtures';

		$paths->vendor = $paths->tmp . '/vendor';

		$paths->testPackageSrc = $paths->fixtures . '/' . self::TEST_PACKAGE_NAME;

		$selfPackageRes = $paths->vendor . '/' . Plugin::SELF_PACKAGE_NAME . '/res';

		$patchSet = 'patchSets/' . self::TEST_PACKAGE_NAME . '.json';
		$paths->patchSet = $selfPackageRes . '/' . $patchSet;
		$paths->patchSetSrc = $paths->fixtures . '/' . $patchSet;

		$paths->patchesSrc = $paths->fixtures . '/patches/' . self::TEST_PACKAGE_NAME;
		$paths->patchesTarget = $selfPackageRes . '/patches/' . self::TEST_PACKAGE_NAME;

		$this->paths = $paths;
	}

	/**
	 * Physically install a package (mock the behaviour of composer library installer)
	 *
	 * @param \Composer\Package\Package $package
	 * @param \Composer\Composer $composer
	 */
	protected function installPackage(Package $package, $composer = NULL) {
		$packagePath = $this->getInstallPath($package);
		$installed = file_exists($packagePath);
		if ($installed) {
			$this->rmdir($packagePath);
		}
		$this->mkdir($packagePath);
		switch ($package->getName()) {
			case Plugin::SELF_PACKAGE_NAME:
				$this->mkdir(dirname($this->paths->patchSet));
				copy($this->paths->patchSetSrc, $this->paths->patchSet);
				if ($installed) {
					// When composer updates this package, the plugin will be
					// registered again {@see Plugin::getSubscribedEvents()}
					// We must mock this, as the plugin creates paths on activation
					$zombiePlugin = new Plugin();
					$zombiePlugin->activate($composer, $this->io);
				}
				break;
			case self::TEST_PACKAGE_NAME:
				exec('cp -r ' . escapeshellarg($this->paths->testPackageSrc) . '/* ' . escapeshellarg($packagePath));
				break;
			default:
				file_put_contents($packagePath . '/composer.json', "{\n}");
		}
	}

	/**
	 * Stick the expected composer environment together
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getComposerMock(array $installedPackages) {
		$config = $this->getMock('Composer\Config');
		$composer = $this->getMock('Composer\Composer', array('getRepositoryManager', 'getInstallationManager'));
		$installMan = $this->getMock('Composer\Installer\InstallationManager', array('getInstallPath'));
		$reposMan = $this->getMock('Composer\Repository\RepositoryManager', array('getLocalRepository'), array($this->io, $config));
		$localRepo = $this->getMock('Composer\Repository\WritableArrayRepository', array('getPackages'));

		$composer
			->expects($this->any())
			->method('getRepositoryManager')
			->will($this->returnValue($reposMan));
		$reposMan
			->expects($this->any())
			->method('getLocalRepository')
			->will($this->returnValue($localRepo));
		$localRepo
			->expects($this->any())
			->method('getPackages')
			->will($this->returnValue(array_values($installedPackages)));

		$composer
			->expects($this->any())
			->method('getInstallationManager')
			->will($this->returnValue($installMan));
		$installMan
			->expects($this->any())
			->method('getInstallPath')
			->will($this->returnCallback(array($this, 'getInstallPath')));

		return $composer;
	}

	/**
	 * Get the downloader mock
	 *
	 * @param type $countDownload
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getDownloaderMock($countDownload = NULL) {
		$downloader = $this->getMock(
			'Netresearch\Composer\Patches\Downloader\DownloaderInterface',
			array('download')
		);
		$downloader
			->expects($countDownload === NULL ? $this->any() : $this->exactly($countDownload))
			->method('download')
			->will($this->returnCallback(array($this, 'downloadCallback')));
		return $downloader;
	}

	/**
	 * Get install path of a package
	 *
	 * @param \Composer\Package\Package $package
	 * @return string
	 */
	public function getInstallPath(Package $package) {
		return $this->paths->vendor . '/' . $package->getName();
	}

	/**
	 * Callback for DownloaderInterface::download mock
	 *
	 * @param string $fileUrl
	 * @param string $filePath
	 * @return string
	 */
	public function downloadCallback($fileUrl, $filePath) {
		$baseUrl = 'https://www.example.com/';
		$baseUrlLen = strlen($baseUrl);
		if (substr($fileUrl, 0, $baseUrlLen) != $baseUrl) {
			$this->fail('patchUrl must begin with ' . $baseUrl);
		}

		$fileName = basename($filePath);
		$targetPath = $this->paths->patchesTarget . '/' . $fileName;

		$patchId = basename($filePath, '.diff');
		$curPos = array_search($patchId, $this->expectedPatchIds);
		if ($curPos === FALSE) {
			$this->fail('Unexpected patch id: ' . $patchId);
		}

		if ($this->downloadFailingPatch && $curPos == count($this->expectedPatchIds) - 1) {
			$fileName = $this->expectedPatchIds[$curPos - 1] . '.diff';
		}
		$srcPath = $this->paths->patchesSrc . '/' . $fileName;

		if (!file_exists($srcPath)) {
			$this->fail(
				'No patch file found with name "' . $fileName . '" ' .
				'(tryed to load from "' . $srcPath . '")'
			);
		}
		if (!file_exists($this->paths->patchesTarget)) {
			mkdir($this->paths->patchesTarget, 0777, TRUE);
		}

		$this->assertEquals($this->paths->patchesTarget, dirname($filePath));

		copy($srcPath, $targetPath);
		return $targetPath;
	}

	/**
	 * Extract path IDs from JSON patchSet file
	 *
	 * @param string $file
	 * @return array
	 */
	protected function extractPatchIds($file) {
		$data = json_decode(file_get_contents($file));
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->fail('Failed reading file ' . $file . ' - JSON error ' . json_last_error());
		}
		return array_keys(get_object_vars($data->patches));
	}

	/**
	 * Run the initial installation
	 *
	 * @param \Composer\Package\Package $testPackage
	 * @return array The installed packages
	 */
	protected function runInstallation(Package $testPackage) {
		$selfPackage = new Package(Plugin::SELF_PACKAGE_NAME, '1.0.0.0', '1.0.0');

		$this->installPackage($selfPackage);

		$composer = $this->getComposerMock(array($selfPackage));
		$plugin = new Plugin();
		$plugin->activate($composer, $this->io);

		$plugin->collect(new PackageEvent('test', $composer, $this->io, TRUE, new InstallOperation($testPackage)));
		$this->installPackage($testPackage);
		$plugin->apply(
			new \Composer\Script\Event('test', $composer, $this->io, TRUE),
			$this->getDownloaderMock(count($this->expectedPatchIds))
		);

		return array($selfPackage, $testPackage);
	}

	/**
	 * Test the initial installation (nothing installed yet)
	 */
	public function testInstallation() {
		$package = new Package('vendor/package', '1.0.0.0', '1.0.0');
		$this->runInstallation($package);
		$this->assertPatchSetApplied($package);
	}

	/**
	 * Test a failing installation: Should end with exception and revert already
	 * applied patches
	 */
	public function testFailingInstallation() {
		$this->downloadFailingPatch = TRUE;
		$package = new Package('vendor/package', '1.0.0.0', '1.0.0');
		try {
			$this->runInstallation($package);
		} catch (\Exception $e) {
			$this->assertInstanceOf('Netresearch\Composer\Patches\PatchCommandException', $e);
		}
		$this->assertPackageIsUnpatched($package);
	}

	/**
	 * Test the update of the patched package and of the patchset package
	 *
	 * @dataProvider provideTestPackages
	 */
	public function testUpdate($testPackage, $finalPackage, $initialPackage, $targetPackage) {
		$installedPackages = $this->runInstallation($testPackage);
		$composer = $this->getComposerMock($installedPackages);
		$plugin = new Plugin();
		$plugin->activate($composer, $this->io);

		$plugin->restore(new PackageEvent('test', $composer, $this->io, TRUE, new UpdateOperation($initialPackage, $targetPackage)));

		// Assert, the backup was restored
		$this->assertPackageIsUnpatched($testPackage);

		// Do the update and apply the patchSet
		$plugin->collect(new PackageEvent('test', $composer, $this->io, TRUE, new UpdateOperation($initialPackage, $targetPackage)));
		$this->installPackage($targetPackage, $composer);
		$plugin->apply(
			new \Composer\Script\Event('test', $composer, $this->io, TRUE),
			$this->getDownloaderMock()
		);

		$this->assertPatchSetApplied($finalPackage);
	}

	/**
	 * Provide arguments for testUpdate()
	 *
	 * @return array
	 */
	public function provideTestPackages() {
		return array(
			array(
				$testPackage = new Package('vendor/package', '1.0.0.0', '1.0.0'),
				$targetPackage = new Package('vendor/package', '1.1.0.0', '1.1.0'),
				$testPackage,
				$targetPackage
			),
			array(
				$testPackage,
				$testPackage,
				new Package(Plugin::SELF_PACKAGE_NAME, '1.0.0.0', '1.0.0'),
				new Package(Plugin::SELF_PACKAGE_NAME, '1.1.0.0', '1.1.0')
			)
		);
	}

	/**
	 * Assert that the package equals the original
	 *
	 * @param \Composer\Package\Package $package
	 */
	protected function assertPackageIsUnpatched(Package $package) {
		$command = 'diff -r ' . escapeshellarg($this->paths->testPackageSrc) . ' ' . escapeshellarg($this->getInstallPath($package));
		exec($command, $output, $return);
		$this->assertEquals(0, $return, 'Failed running command ' . $command . ":\n" . implode("\n", $output));
		$this->assertCount(0, $output);
	}

	/**
	 * Assert that actions (D, A, M) in the patches in the patchSet were actually
	 * executed
	 *
	 * @param \Composer\Package\Package $package
	 */
	protected function assertPatchSetApplied(Package $package) {
		// Extract the actions from the patchSet
		$del = array();
		$add = array();
		$mod = array();
		foreach ($this->expectedPatchIds as $patchId) {
			$patch = new Patch(
				$this->paths->patchesTarget . '/' . $patchId . '.diff',
				new \stdClass()
			);

			$tmpDel = array_diff($patch->getFileDeletions(), $patch->getFileAdditions());
			$tmpAdd = array_diff($patch->getFileAdditions(), $patch->getFileDeletions());
			$tmpMod = array_intersect($patch->getFileDeletions(), $patch->getFileAdditions());

			$del = array_unique(array_merge(array_diff($del, $tmpAdd), $tmpDel));
			$add = array_unique(array_merge(array_diff($add, $tmpMod), $tmpAdd));
			$mod = array_unique(array_merge(array_diff($mod, $del, $add), $tmpMod));
		}

		// Assert actions were applied
		$srcPath = $this->paths->testPackageSrc . '/';
		$packagePath = $this->getInstallPath($package) . '/';
		foreach ($del as $file) {
			$this->assertFileExists($srcPath . $file);
			$this->assertFileNotExists($packagePath . $file);
		}
		foreach ($add as $file) {
			$this->assertFileNotExists($srcPath . $file);
			$this->assertFileExists($packagePath . $file);
		}
		foreach ($mod as $file) {
			$this->assertFileNotEquals($srcPath . $file, $packagePath . $file);
		}
	}
}