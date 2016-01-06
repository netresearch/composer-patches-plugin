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

use Composer\Package\Version\VersionParser;

/**
 * Model, representing a patchSet
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class PatchSet {
	/**
	 * Path to this patchSet file (JSON)
	 * @var array|object|string
	 */
	protected $source;

	/**
	 * @var array
	 */
	protected $patches;

	/**
	 * @var Downloader\DownloaderInterface
	 */
	protected $downloader;

	/**
	 * Contains the data downloaded from the URLs
	 * @var array
	 */
	protected static $downloadCache = array();

	/**
	 * Constructor - set the paths
	 *
	 * @param array|object|string $source
	 * @param Downloader\DownloaderInterface $downloader
	 */
	public function __construct($source, Downloader\DownloaderInterface $downloader) {
		$this->source = $source;
		$this->setDownloader($downloader);
	}

	/**
	 * Get the downloader
	 *
	 * @return Downloader\DownloaderInterface
	 * @throws Exception
	 */
	public function getDownloader() {
		if (!$this->downloader) {
			throw new Exception('No downloader set');
		}
		return $this->downloader;
	}

	public function setDownloader(Downloader\DownloaderInterface $downloader) {
		$this->downloader = $downloader;
	}

	/**
	 * Get the patches for a package, identified by name and version
	 *
	 * @param string $name
	 * @param string $version
	 *
	 * @throws Exception
	 *
	 * @return Patch[]
	 */
	public function getPatches($name, $version) {
		if (!is_array($this->patches)) {
			$this->source = $this->read($this->source);
			$this->patches = array();
		}
		if (!array_key_exists($name, $this->patches)) {
			$this->source[$name] = $this->read($this->source, $name);
			$this->patches[$name] = array();
		}
		if (!array_key_exists($version, $this->patches[$name])) {
			$patchInfos = $this->read($this->source[$name]);
			if ($this->isPatch($patchInfos)) {
				$rawPatches = array($patchInfos);
			} else {
				$rawPatches = array();
				$hasConstraints = null;
				if (class_exists('Composer\Semver\Constraint\Constraint')) {
					$constraintClass = 'Composer\Semver\Constraint\Constraint';
				} else {
					$constraintClass = 'Composer\Package\LinkConstraint\VersionConstraint';
				}
				$requiredConstraint = new $constraintClass('==', $version);
				$versionParser = new VersionParser();
				foreach ($patchInfos as $constraint => $patchInfo) {
					if ($this->isPatch($patchInfo)) {
						$isConstraint = false;
						$rawPatches[] = $patchInfo;
					} else {
						$patchInfo = $this->read($patchInfo);
						$isConstraint = true;
						$constraint = $versionParser->parseConstraints($constraint);
						if ($constraint->matches($requiredConstraint)) {
							foreach ($patchInfo as $i => $rawPatch) {
								if (!$this->isPatch($rawPatch)) {
									throw new Exception("Entry {$name}.{$constraint}[{$i}] is not a valid patch");
								}
								$rawPatches[] = $rawPatch;
							}
						}
					}
					if ($hasConstraints !== null) {
						if ($hasConstraints !== $isConstraint) {
							throw new Exception('Mixing patches with constraints and without constraints is not possible');
						}
					} else {
						$hasConstraints = $isConstraint;
					}
				}
			}
			$patches = array();
			foreach ($rawPatches as $rawPatch) {
				$patch = new Patch((object) $rawPatch, $this);
				$patches[$patch->getChecksum()] = $patch;
			}
			$this->patches[$name][$version] = $patches;
		}
		return $this->patches[$name][$version];
	}

	/**
	 * Determine if this config is a patches config
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	protected function isPatch($config)
	{
		return is_array($config) && array_key_exists('url', $config);
	}

	/**
	 * Read in the JSON file
	 *
	 * @return array|string
	 * @throws Exception
	 */
	protected function read($data, $key = NULL) {
		if ($data === NULL || $data === '') {
			throw new Exception('Empty data entry');
		}
		if (is_string($data)) {
			$data = $this->getDownloader()->getJson($data);
		}
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		if (!is_array($data)) {
			throw new Exception('Data must be array');
		}
		if ($key !== NULL) {
			if (!array_key_exists($key, $data)) {
				return array();
			}
			if (is_string($data[$key]) && array_key_exists($data[$key], $data)) {
				$key = $data[$key];
			}
			return $this->read($data[$key]);
		}
		return $data;
	}
}