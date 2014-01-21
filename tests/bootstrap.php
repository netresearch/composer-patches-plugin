<?php
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

error_reporting(E_ALL);

$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadFile)) {
	echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL .
		'curl -sS https://getcomposer.org/installer | php' . PHP_EOL .
		'php composer.phar install' . PHP_EOL;
	exit(1);
}
$loader = require $autoloadFile;
$loader->add('Netresearch\Test', __DIR__);
?>