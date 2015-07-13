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
 * Exception for patch command execution errors
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class PatchCommandException extends Exception {
	/**
	 * Constructor - pass it {@see exec()} $output
	 *
	 * @param array $output
	 */
	public function __construct($command, $output, Patch $patch, $dryRun = FALSE) {
		$output = 'Patch ' . $patch->getChecksum() . ' ' . ($dryRun ? 'would fail' : 'failed') . "!\n" .
			'Error executing command "' . $command . '":' . "\n" .
			(is_array($output) ? implode("\n", $output) : $output);
		parent::__construct($output);
	}
}