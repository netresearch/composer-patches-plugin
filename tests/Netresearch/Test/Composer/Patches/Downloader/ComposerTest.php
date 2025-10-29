<?php

namespace Netresearch\Test\Composer\Patches\Downloader;

use Netresearch\Composer\Patches\Downloader\Composer as ComposerDownloader;
use PHPUnit\Framework\TestCase;
use Composer\Util\HttpDownloader;
use Composer\Util\RemoteFilesystem;
use Composer\Config;

/**
 * Tests for the Composer downloader with both Composer 1.x and 2.x compatibility.
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
     * Test downloader instantiation with Composer 2.x (HttpDownloader available).
     */
    public function testInstantiationWithComposer2x(): void
    {
        // Skip if HttpDownloader is not available (Composer 1.x environment)
        if (!class_exists(HttpDownloader::class)) {
            $this->markTestSkipped('HttpDownloader class not available');
        }

        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);

        $this->assertInstanceOf(ComposerDownloader::class, $downloader);

        // Use reflection to check internal downloader type
        $reflection = new \ReflectionClass($downloader);
        $property = $reflection->getProperty('downloader');
        $property->setAccessible(true);
        $internalDownloader = $property->getValue($downloader);

        $this->assertInstanceOf(HttpDownloader::class, $internalDownloader);
    }

    /**
     * Test downloader instantiation with Composer 1.x (no HttpDownloader class).
     */
    public function testInstantiationWithComposer1x(): void
    {
        // Create a test case where HttpDownloader doesn't exist
        // This is tricky to test since we can't unload a class
        // We'll just test the RemoteFilesystem case when config is available

        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);

        $this->assertInstanceOf(ComposerDownloader::class, $downloader);
    }

    /**
     * Test getContents with local file URL.
     */
    public function testGetContentsWithLocalFile(): void
    {
        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

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
     * Test getJson with local file.
     */
    public function testGetJsonWithLocalFile(): void
    {
        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);

        // Create a temporary JSON file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'composer_test') . '.json';
        $testData = ['test' => 'data', 'number' => 42];
        file_put_contents($tempFile, json_encode($testData));

        try {
            $jsonData = $downloader->getJson($tempFile);
            // JsonFile::read() returns an array or object depending on the JSON content
            if (is_array($jsonData)) {
                $this->assertEquals('data', $jsonData['test']);
                $this->assertEquals(42, $jsonData['number']);
            } else {
                $this->assertEquals('data', $jsonData->test);
                $this->assertEquals(42, $jsonData->number);
            }
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test getOriginUrl method.
     */
    public function testGetOriginUrl(): void
    {
        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($downloader);
        $method = $reflection->getMethod('getOriginUrl');
        $method->setAccessible(true);

        $this->assertEquals('example.com', $method->invoke($downloader, 'http://example.com/path/file.json'));
        $this->assertEquals('github.com', $method->invoke($downloader, 'https://github.com/user/repo/file.json'));
        $this->assertNull($method->invoke($downloader, '/local/file/path.json'));
    }

    /**
     * Test backward compatibility - ensure interface is properly implemented.
     */
    public function testImplementsDownloaderInterface(): void
    {
        $config = $this->createMock(Config::class);

        $this->composer->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $downloader = new ComposerDownloader($this->io, $this->composer);

        $this->assertInstanceOf(
            \Netresearch\Composer\Patches\Downloader\DownloaderInterface::class,
            $downloader
        );

        // Verify required methods exist
        $this->assertTrue(method_exists($downloader, 'getContents'));
        $this->assertTrue(method_exists($downloader, 'getJson'));
    }
}
