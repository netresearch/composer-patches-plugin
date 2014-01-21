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

/**
 * Base test case
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase {
	/**
	 * Recursively delete a directory when it exists
	 *
	 * @param string $dir
	 * @return boolean
	 */
	protected function rmdir($dir) {
		if (!file_exists($dir)) {
			return;
		}
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			(is_dir($path)) ? $this->rmdir($path) : unlink($path);
		}
		return rmdir($dir);
	}

	/**
	 * Recursively create a directory
	 *
	 * @param string $dir
	 */
	protected function mkdir($dir) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, TRUE);
		}
	}

	/**
	 * Check if the content of two files is the same
	 *
	 * @param string $file1
	 * @param string $file2
	 * @return boolean TRUE when same, FALSE when not
	 */
	protected function compareFiles($file1, $file2) {
		return strcmp(file_get_contents($file1), file_get_contents($file2)) === 0;
	}
}
