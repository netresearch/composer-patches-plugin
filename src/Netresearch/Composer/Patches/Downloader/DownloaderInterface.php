<?php

namespace Netresearch\Composer\Patches\Downloader;

/**
 * Downloader interface
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
interface DownloaderInterface
{
    /**
     * Download the file and return its contents
     *
     * @param string $url The URL from where to download
     *
     * @return string Contents of the URL
     */
    public function getContents($url);

    /**
     * Download file and decode the JSON string to PHP object
     *
     * @param $url
     *
     * @return mixed
     */
    public function getJson($url);
}
