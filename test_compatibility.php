<?php
/**
 * Test script to verify Composer 2.x compatibility
 */

require_once __DIR__ . '/vendor/autoload.php';

use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Factory;
use Netresearch\Composer\Patches\Downloader\Composer as ComposerDownloader;

echo "Testing Composer 2.x compatibility...\n";

$io = new NullIO();
$composer = Factory::create($io);

echo "Composer version: " . Composer::getVersion() . "\n";

try {
    // Test downloader instantiation
    $downloader = new ComposerDownloader($io, $composer);
    echo "✓ Downloader instantiated successfully\n";
    
    // Test if HttpDownloader is available (Composer 2.x)
    if (class_exists('Composer\Util\HttpDownloader')) {
        echo "✓ HttpDownloader class available (Composer 2.x detected)\n";
    } else {
        echo "✓ RemoteFilesystem fallback (Composer 1.x detected)\n";
    }
    
    // Check which downloader is actually being used internally
    $reflection = new ReflectionClass($downloader);
    $property = $reflection->getProperty('downloader');
    $property->setAccessible(true);
    $internalDownloader = $property->getValue($downloader);
    echo "✓ Internal downloader type: " . get_class($internalDownloader) . "\n";
    
    // Test local file access
    $tempFile = tempnam(sys_get_temp_dir(), 'composer_test');
    file_put_contents($tempFile, '{"test": "data"}');
    
    try {
        $content = $downloader->getContents($tempFile);
        echo "✓ getContents() works: " . trim($content) . "\n";
        
        $jsonData = $downloader->getJson($tempFile);
        echo "✓ getJson() works: " . json_encode($jsonData) . "\n";
    } finally {
        unlink($tempFile);
    }
    
    echo "\n✅ All tests passed! The plugin should work with this Composer version.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}