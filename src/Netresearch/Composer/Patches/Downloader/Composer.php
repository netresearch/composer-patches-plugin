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

use Composer\Util\RemoteFilesystem;
use Composer\Util\HttpDownloader;
use Composer\Config;
use Composer\Factory;

/**
 * Downloader, which uses the composer RemoteFilesystem or HttpDownloader (Composer 2.x)
 */
class Composer implements DownloaderInterface
{
    /**
     * @var RemoteFilesystem|HttpDownloader
     */
    protected $downloader;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * Construct the downloader with backward compatibility for Composer 1.x and 2.x
     *
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer $composer
     */
    public function __construct(\Composer\IO\IOInterface $io, \Composer\Composer $composer)
    {
        $this->io = $io;

        // Check if HttpDownloader class exists (Composer 2.x)
        if (class_exists('Composer\Util\HttpDownloader')) {
            // Composer 2.x: Create HttpDownloader
            $config = $composer->getConfig();
            $this->downloader = new HttpDownloader($io, $config);
        } else {
            // Composer 1.x fallback: Use RemoteFilesystem
            $config = $composer->getConfig() ?: Factory::createConfig($io);
            $this->downloader = new RemoteFilesystem($io, $config);
        }
    }

    /**
     * Get the origin URL required by composer rfs
     *
     * @param  string $url
     * @return string
     */
    protected function getOriginUrl($url)
    {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * Download the file and return its contents
     *
     * @param  string $url The URL from where to download
     * @return string Contents of the URL
     */
    public function getContents($url)
    {
        $originUrl = $this->getOriginUrl($url);

        if (is_null($originUrl)) {
            return file_get_contents($url);
        }

        // Use appropriate method based on downloader type
        if ($this->downloader instanceof HttpDownloader) {
            // Composer 2.x: HttpDownloader
            $response = $this->downloader->get($url);
            return $response->getBody();
        } else {
            // Composer 1.x: RemoteFilesystem
            return $this->downloader->getContents($originUrl, $url, false);
        }
    }

    /**
     * Download file and decode the JSON string to PHP object
     *
     * @param  string $url
     * @return \stdClass
     */
    public function getJson($url)
    {
        // Use appropriate JsonFile constructor based on downloader type
        if ($this->downloader instanceof HttpDownloader) {
            // Composer 2.x: Use HttpDownloader
            $json = new \Composer\Json\JsonFile($url, $this->downloader);
        } else {
            // Composer 1.x: Use RemoteFilesystem
            $json = new \Composer\Json\JsonFile($url, $this->downloader);
        }
        
        return $json->read();
    }
}
