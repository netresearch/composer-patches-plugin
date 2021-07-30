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

/**
 * Downloader, which uses the composer RemoteFilesystem
 */
class Composer implements DownloaderInterface
{
    /**
     * @var RemoteFilesystem
     */
    protected $remoteFileSystem;

    /**
     * Construct the RFS
     *
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(\Composer\IO\IOInterface $io, \Composer\Config $config)
    {
        $this->remoteFileSystem = new RemoteFilesystem($io, $config);
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
     * Translate 
     * @param type $url
     */
    protected function resolveVendorPath($url)
    {
        // Alternative vendor path defined and patch file starts with `vendor/`
        if (getenv('COMPOSER_VENDOR_DIR') && strpos($url, 'vendor/') === 0) {
            return getenv('COMPOSER_VENDOR_DIR') . DIRECTORY_SEPARATOR . \mb_substr($url, mb_strlen('vendor/'));
        }

        return $url;
    }

    /**
     * Download the file and return its contents
     * 
     * @param string $url The URL from where to download
     * @return string Contents of the URL
     */
    public function getContents($url)
    {
        $path = $this->resolveVendorPath($url);
        return $this->cache[$url] = $this->remoteFileSystem->getContents($this->getOriginUrl($url), $path, false);
    }
    
    /**
     * Download file and decode the JSON string to PHP object
     *
     * @param  string $json
     * @return stdClass
     */
    public function getJson($url)
    {
        $json = new \Composer\Json\JsonFile($url, $this->remoteFileSystem);
        return $json->read();
    }
}
