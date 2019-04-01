<?php

namespace Netresearch\Composer\Patches;

/**
 * Base Exception for PatchSet package
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Exception extends \ErrorException
{
    /**
     * Constructor - message is required only
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
