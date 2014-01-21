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
 * Base Exception for PatchSet package
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Exception extends \ErrorException {
	/**
	 * Constructor - message is required only
	 *
	 * @param string $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}
?>