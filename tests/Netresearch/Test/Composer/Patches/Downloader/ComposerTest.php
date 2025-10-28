<?php

namespace Netresearch\Test\Composer\Patches\Downloader;

use Netresearch\Composer\Patches\Downloader\Composer as ComposerDownloader;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Composer downloader with both Composer 1.x and 2.x compatibility
 */
class ComposerTest extends TestCase
{
    /**
     * @var \Composer\IO\IOInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $io;

    /**
     * @var \Composer\Composer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $composer;

    protected function setUp(): void
    {
        $this->io = $this->createMock(\Composer\IO\IOInterface::class);
        $this->composer = $this->createMock(\Composer\Composer::class);
    }

    /**
     * Test downloader instantiation with Composer 2.x (HttpDownloader available)
     */
    public function testInstantiationWithComposer2x()
    {
        $httpDownloader = $this->createMock(\Composer\Util\HttpDownloader::class);
        
        $this->composer->expects($this->once())
            ->method('getHttpDownloader')
            ->willReturn($httpDownloader);

        $downloader = new ComposerDownloader($this->io, $this->composer);
        
        $this->assertInstanceOf(ComposerDownloader::class, $downloader);
    }

    /**
     * Test downloader instantiation with Composer 1.x (no HttpDownloader method)
     */
    public function testInstantiationWithComposer1x()
    {
        $config = $this->createMock(\Composer\Config::class);
        
        // Mock composer without getHttpDownloader method to simulate Composer 1.x
        $this->composer = $this->createMock(\Composer\Composer::class);
        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);
        
        $this->assertInstanceOf(ComposerDownloader::class, $downloader);
    }

    /**
     * Test getContents with local file URL
     */
    public function testGetContentsWithLocalFile()
    {
        $httpDownloader = $this->createMock(\Composer\Util\HttpDownloader::class);
        
        $this->composer->expects($this->once())
            ->method('getHttpDownloader')
            ->willReturn($httpDownloader);

        $downloader = new ComposerDownloader($this->io, $this->composer);
        
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'composer_test');
        file_put_contents($tempFile, 'test content');
        
        try {
            $content = $downloader->getContents($tempFile);
            $this->assertEquals('test content', $content);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test getContents with HTTP URL using Composer 2.x HttpDownloader
     */
    public function testGetContentsWithHttpUrlComposer2x()
    {
        $httpDownloader = $this->createMock(\Composer\Util\HttpDownloader::class);
        $response = $this->createMock(\Composer\Util\Http\Response::class);
        
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn('remote content');
            
        $httpDownloader->expects($this->once())
            ->method('get')
            ->with('http://example.com/test.txt')
            ->willReturn($response);
        
        $this->composer->expects($this->once())
            ->method('getHttpDownloader')
            ->willReturn($httpDownloader);

        $downloader = new ComposerDownloader($this->io, $this->composer);
        
        $content = $downloader->getContents('http://example.com/test.txt');
        $this->assertEquals('remote content', $content);
    }
}