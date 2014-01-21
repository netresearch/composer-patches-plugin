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

/**
 * This is just a noop installer to enable packages of type "patches"
 * The patch application is done in the plugin and is not limited to
 * "patches"-packages: Every package that has patches in it's extras
 * is respected there.
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Installer extends \Composer\Installer\NoopInstaller {
	/**
	 * Supports packages of type "patches"
	 * 
	 * @param string $packageType
	 * @return boolean
	 */
	public function supports($packageType) {
		return $packageType == 'patches';
	}
}
