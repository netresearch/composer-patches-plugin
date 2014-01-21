<?php
namespace Netresearch\Composer\Patches\Downloader;

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
 * Downloader interface
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
interface DownloaderInterface {
	/**
	 * Download the file and return its contents
	 * 
	 * @param string $url The URL from where to download
	 * @return string Contents of the URL
	 */
	public function getContents($url);

	/**
	 * Download file and decode the JSON string to PHP object
	 *
	 * @param string $json
	 * @return stdClass
	 */
	public function getJson($url);
}
?>