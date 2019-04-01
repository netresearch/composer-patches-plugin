<?php

namespace Netresearch\Composer\Patches;

/**
 * Patch model class
 *
 * @property-read string $id    The ID of the patch
 * @property-read string $type  Type of the patch
 * @property-read string $url   URL to the patch
 * @property-read string $title Title of the patch
 * @property-read string $args  Patch command additional arguments
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Patch
{
    /**
     * Info object created by {@see PatchSet::process()}
     * @var \stdClass
     * @
     */
    protected $info;

    /**
     * The content of the patch file
     * @var string
     */
    protected $content;

    /**
     * File deletions/replacements inside the patch
     * @var array
     */
    protected $fileDeletions;

    /**
     * File additions inside the patch
     * @var array
     */
    protected $fileAdditions;

    /**
     * @var PatchSet
     */
    protected $patchSet;

    protected $checksum;

    /**
     * Construct with $info from {@see PatchSet::process()}
     *
     * @param \stdClass                              $info
     * @param \Netresearch\Composer\Patches\PatchSet $patchSet
     *
     * @throws \Netresearch\Composer\Patches\Exception
     */
    public function __construct(\stdClass $info, PatchSet $patchSet)
    {
        $this->info     = $info;
        $this->patchSet = $patchSet;
        $this->checksum = sha1($this->read());
        if (isset($this->info->sha1) && $this->info->sha1 !== $this->checksum) {
            throw new Exception("Expected checksum '{$this->info->sha1}' but got '{$this->checksum}'");
        }
    }

    /**
     * Read in the patch
     *
     * @return string
     * @throws Exception
     */
    protected function read()
    {
        if ($this->content) {
            return $this->content;
        }

        $this->content = $this->patchSet->getDownloader()->getContents($this->info->url);
        return $this->content;
    }

    /**
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Get a info property
     *
     * @param string $name
     *
     * @return string|mixed
     */
    public function __get($name)
    {
        return $this->info->{$name};
    }

    /**
     * Check if a info property is set
     *
     * @param string $name
     *
     * @return string
     */
    public function __isset($name)
    {
        return isset($this->info->{$name});
    }

    /**
     * Get the files, deleted (or replaced) by this patch
     *
     * @return array
     * @throws \Netresearch\Composer\Patches\Exception
     */
    public function getFileDeletions()
    {
        if (is_array($this->fileDeletions)) {
            return $this->fileDeletions;
        }
        return $this->fileDeletions = $this->getActionFiles('-');
    }

    /**
     * Find the file additions/deletions within the patch file
     *
     * @param string $action '-' or '+'
     *
     * @return array
     * @throws Exception
     */
    protected function getActionFiles($action)
    {
        $prefix = preg_quote(str_repeat($action, 3));
        $p1     = ($action == '-') ? 'a' : 'b';
        preg_match_all('/^' . $prefix . ' (.+)$/m', $this->read(), $matches);
        $paths = [];
        foreach ($matches[1] as $match) {
            if ($match === '/dev/null') {
                continue;
            }
            $slashPos = strpos($match, '/');
            if (substr($match, 0, $slashPos) !== $p1) {
                throw new Exception('Unexpected path: ' . $match);
            }
            $paths[] = substr($match, $slashPos + 1);
        }
        return $paths;
    }

    /**
     * Get the files, added by this patch
     *
     * @return array
     * @throws \Netresearch\Composer\Patches\Exception
     */
    public function getFileAdditions()
    {
        if (is_array($this->fileAdditions)) {
            return $this->fileAdditions;
        }
        return $this->fileAdditions = $this->getActionFiles('+');
    }

    /**
     * Apply the patch
     *
     * @param string  $toPath
     * @param boolean $dryRun
     *
     * @throws Exception
     * @throws PatchCommandException
     */
    public function apply($toPath, $dryRun = false)
    {
        $this->runCommand($toPath, false, $dryRun);
    }

    /**
     * Run the patch command
     *
     * @param      $toPath
     * @param bool $revert
     * @param bool $dryRun
     *
     * @throws \Netresearch\Composer\Patches\Exception
     * @throws \Netresearch\Composer\Patches\PatchCommandException
     */
    protected function runCommand($toPath, $revert = false, $dryRun = false)
    {
        $command = $this->whichPatchCmd() . ' -f -p1 --no-backup-if-mismatch -r -';

        if ($revert) {
            $command .= ' -R';
        }
        if (isset($this->info->args)) {
            $command .= ' ' . $this->info->args;
        }
        if ($dryRun) {
            $command .= ' --dry-run';
        }

        if ($this->executeProcess($command, $toPath, $this->read(), $stdout) > 0) {
            throw new PatchCommandException($command, $stdout, $this, $dryRun);
        }
    }

    /**
     * Locate the patch executable
     *
     * @throws Exception
     *
     * @return string
     */
    protected function whichPatchCmd()
    {
        static $patchCommand = null;
        if (!$patchCommand) {
            $exitCode     = $output = null;
            $patchCommand = exec('which patch', $output, $exitCode);
            if (0 !== $exitCode || !is_executable($patchCommand)) {
                throw new Exception("Cannot find the 'patch' executable command - use your o/s package manager like 'sudo yum install patch'");
            }
        }
        return $patchCommand;
    }

    /**
     * Process execution wrapper adapted from
     * @link http://omegadelta.net/2012/02/08/stdin-stdout-stderr-with-proc_open-in-php/
     *
     * @param string $command
     * @param string $cwd
     * @param string $stdin
     * @param string $stdout
     * @param string $stderr
     *
     * @return int
     */
    protected function executeProcess($command, $cwd, $stdin, &$stdout, &$stderr = null)
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process        = proc_open($command, $descriptorSpec, $pipes, $cwd);
        $txOff          = 0;
        $txLen          = strlen($stdin);

        $stdout     = '';
        $stderr     = '';
        $stdoutDone = false;
        $stderrDone = false;

        // Make stdin/stdout/stderr non-blocking
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        if ($txLen == 0) {
            fclose($pipes[0]);
        }

        while (true) {
            // The program's stdout/stderr
            $rx = [];
            if (!$stdoutDone) {
                $rx[] = $pipes[1];
            }
            if (!$stderrDone) {
                $rx[] = $pipes[2];
            }
            // The program's stdin
            $tx = [];
            if ($txOff < $txLen) {
                $tx[] = $pipes[0];
            }
            $ex = null;
            // Block til r/w possible
            stream_select($rx, $tx, $ex, null, null);
            if (!empty($tx)) {
                $txRet = fwrite($pipes[0], substr($stdin, $txOff, 8192));
                if ($txRet !== false) {
                    $txOff += $txRet;
                }
                if ($txOff >= $txLen) {
                    fclose($pipes[0]);
                }
            }
            foreach ($rx as $r) {
                if ($r == $pipes[1]) {
                    $stdout .= fread($pipes[1], 8192);
                    if (feof($pipes[1])) {
                        fclose($pipes[1]);
                        $stdoutDone = true;
                    }
                } elseif ($r == $pipes[2]) {
                    $stderr .= fread($pipes[2], 8192);
                    if (feof($pipes[2])) {
                        fclose($pipes[2]);
                        $stderrDone = true;
                    }
                }
            }
            if (!is_resource($process)) {
                break;
            }
            if ($txOff >= $txLen && $stdoutDone && $stderrDone) {
                break;
            }
        }

        return proc_close($process);
    }

    /**
     * Revert the patch
     *
     * @param      $toPath
     * @param bool $dryRun
     *
     * @throws \Netresearch\Composer\Patches\Exception
     * @throws \Netresearch\Composer\Patches\PatchCommandException
     */
    public function revert($toPath, $dryRun = false)
    {
        $this->runCommand($toPath, true, $dryRun);
    }
}
