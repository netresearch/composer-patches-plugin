<?php
/**
 * Simple bootstrap for test environment
 */

// Include the composer autoloader
$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

// Set a reasonable timeout for tests
ini_set('default_socket_timeout', '30');