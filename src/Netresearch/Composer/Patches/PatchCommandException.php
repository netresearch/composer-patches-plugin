<?php

namespace Netresearch\Composer\Patches;

/**
 * Exception for patch command execution errors
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class PatchCommandException extends Exception
{
    /**
     * Constructor - pass it {@see exec()} $output
     *
     * @param array $output
     */
    public function __construct($command, $output, Patch $patch, $dryRun = false)
    {
        $output = 'Patch ' . $patch->getChecksum() . ' ' . ($dryRun ? 'would fail' : 'failed') . "!\n" .
                  'Error executing command "' . $command . '":' . "\n" .
                  (is_array($output) ? implode("\n", $output) : $output);
        parent::__construct($output);
    }
}
