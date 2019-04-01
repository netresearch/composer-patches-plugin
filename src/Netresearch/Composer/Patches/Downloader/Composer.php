<?php

namespace Netresearch\Composer\Patches\Downloader;

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
     * Very simple cache system
     * @var array
     */
    protected $cache = [];

    /**
     * Construct the RFS
     *
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(\Composer\IO\IOInterface $io)
    {
        $this->remoteFileSystem = new RemoteFilesystem($io);
    }

    /**
     * Download the file and return its contents
     *
     * @param string $url The URL from where to download
     *
     * @return string Contents of the URL
     */
    public function getContents($url)
    {
        if (array_key_exists($url, $this->cache)) {
            return $this->cache[$url];
        }
        return $this->cache[$url] = $this->remoteFileSystem->getContents($this->getOriginUrl($url), $url, false);
    }

    /**
     * Get the origin URL required by composer rfs
     *
     * @param string $url
     *
     * @return string
     */
    protected function getOriginUrl($url)
    {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * Download file and decode the JSON string to PHP object
     *
     * @param $url
     *
     * @return mixed
     */
    public function getJson($url)
    {
        $key = 'json://' . $url;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        $json = new \Composer\Json\JsonFile($url, $this->remoteFileSystem);
        return $this->cache[$key] = $json->read();
    }
}
