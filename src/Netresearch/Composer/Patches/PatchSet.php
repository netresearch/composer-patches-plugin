<?php

namespace Netresearch\Composer\Patches;

use Composer\Package\Version\VersionParser;

/**
 * Model, representing a patchSet
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class PatchSet
{
    /**
     * Contains the data downloaded from the URLs
     * @var array
     */
    protected static $downloadCache = [];
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
     * Constructor - set the paths
     *
     * @param array|object|string            $source
     * @param Downloader\DownloaderInterface $downloader
     */
    public function __construct($source, Downloader\DownloaderInterface $downloader)
    {
        $this->source = $source;
        $this->setDownloader($downloader);
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
    public function getPatches($name, $version)
    {
        if (!is_array($this->patches)) {
            $this->source  = $this->read($this->source);
            $this->patches = [];
        }
        if (!array_key_exists($name, $this->patches)) {
            $this->source[$name]  = $this->read($this->source, $name);
            $this->patches[$name] = [];
        }
        if (!array_key_exists($version, $this->patches[$name])) {
            $patchInfos = $this->read($this->source[$name]);
            if ($this->isPatch($patchInfos)) {
                $rawPatches = [$patchInfos];
            } else {
                $rawPatches     = [];
                $hasConstraints = null;
                if (class_exists('Composer\Semver\Constraint\Constraint')) {
                    $constraintClass = 'Composer\Semver\Constraint\Constraint';
                } else {
                    $constraintClass = 'Composer\Package\LinkConstraint\VersionConstraint';
                }
                $requiredConstraint = new $constraintClass('==', $version);
                $versionParser      = new VersionParser();
                foreach ($patchInfos as $constraint => $patchInfo) {
                    if ($this->isPatch($patchInfo)) {
                        $isConstraint = false;
                        $rawPatches[] = $patchInfo;
                    } else {
                        $patchInfo    = $this->read($patchInfo);
                        $isConstraint = true;
                        $constraint   = $versionParser->parseConstraints($constraint);
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
            $patches = [];
            foreach ($rawPatches as $rawPatch) {
                $patch                          = new Patch((object) $rawPatch, $this);
                $patches[$patch->getChecksum()] = $patch;
            }
            $this->patches[$name][$version] = $patches;
        }
        return $this->patches[$name][$version];
    }

    /**
     * Read in the JSON file
     *
     * @return array|string
     * @throws Exception
     */
    protected function read($data, $key = null)
    {
        if ($data === null || $data === '') {
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
        if ($key !== null) {
            if (!array_key_exists($key, $data)) {
                return [];
            }
            if (is_string($data[$key]) && array_key_exists($data[$key], $data)) {
                $key = $data[$key];
            }
            return $this->read($data[$key]);
        }
        return $data;
    }

    /**
     * Get the downloader
     *
     * @return Downloader\DownloaderInterface
     * @throws Exception
     */
    public function getDownloader()
    {
        if (!$this->downloader) {
            throw new Exception('No downloader set');
        }
        return $this->downloader;
    }

    public function setDownloader(Downloader\DownloaderInterface $downloader)
    {
        $this->downloader = $downloader;
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
}
