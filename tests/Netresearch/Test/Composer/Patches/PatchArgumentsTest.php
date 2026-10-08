<?php

namespace Netresearch\Test\Composer\Patches;

use Netresearch\Composer\Patches\Downloader\DownloaderInterface;
use Netresearch\Composer\Patches\Exception;
use Netresearch\Composer\Patches\PatchSet;
use PHPUnit\Framework\TestCase;

/**
 * The patch definition's "args" reach the patch command as plain arguments,
 * never through a shell, and only supported options are accepted.
 */
class PatchArgumentsTest extends TestCase
{
    private const PATCH = "--- a/a.txt\n+++ b/a.txt\n@@ -1 +1 @@\n-hello\n+world\n";

    /**
     * @var string
     */
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/patch-args-' . bin2hex(random_bytes(6));
        mkdir($this->dir);
        file_put_contents($this->dir . '/a.txt', "hello\n");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->dir);
    }

    /**
     * Build the patches as a package other than the root package supplies them.
     *
     * @param mixed $args
     *
     * @return \Netresearch\Composer\Patches\Patch[]
     */
    private function patchesWithArgs($args): array
    {
        $downloader = new class () implements DownloaderInterface {
            public function getContents($url)
            {
                return PatchArgumentsTest::patchContents();
            }

            public function getJson($url)
            {
                return [];
            }
        };
        $source = ['vendor/target' => [['url' => 'dependency.patch', 'args' => $args]]];

        return (new PatchSet($source, $downloader))->getPatches('vendor/target', '1.0.0');
    }

    public static function patchContents(): string
    {
        return self::PATCH;
    }

    public function testSupportedOptionsAreApplied(): void
    {
        $patches = $this->patchesWithArgs('-l --fuzz=3');

        foreach ($patches as $patch) {
            $patch->apply($this->dir);
        }

        $this->assertSame("world\n", file_get_contents($this->dir . '/a.txt'));
    }

    public function testShellSyntaxInArgsIsNotInterpreted(): void
    {
        $marker = $this->dir . '/marker';

        try {
            foreach ($this->patchesWithArgs('-l ; touch marker') as $patch) {
                $patch->apply($this->dir);
            }
        } catch (Exception $e) {
            // Rejecting the definition is as good as running it without a shell.
        }

        $this->assertFileDoesNotExist($marker);
    }

    public function testUnsupportedArgsAreRejected(): void
    {
        foreach (self::unsupportedArgs() as $description => $args) {
            try {
                $this->patchesWithArgs($args);
            } catch (Exception $e) {
                continue;
            }
            $this->fail('Args were accepted: ' . $description);
        }
        $this->assertFileDoesNotExist($this->dir . '/marker');
    }

    public static function unsupportedArgs(): array
    {
        return [
            'output file' => '-o /tmp/target',
            'long output file' => '--output=/tmp/target',
            'abbreviated long option' => '--out=/tmp/target',
            'directory' => '--directory=/',
            'file operand' => '/etc/target',
            'command separator' => '-l ; touch marker',
            'command substitution' => '$(touch marker)',
            'backtick substitution' => '`touch marker`',
            'pipe' => '-l | tee marker',
            'redirect' => '-l > marker',
            'newline' => "-l\ntouch marker",
            'array value' => ['-l'],
        ];
    }
}
