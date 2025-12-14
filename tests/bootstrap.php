<?php
/**
 * PHPUnit bootstrap file for Ad Code Manager plugin tests.
 *
 * @package Automattic\AdCodeManager
 */

declare( strict_types=1 );

namespace Automattic\AdCodeManager\Tests;

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Check for a `--testsuite Unit` arg when calling phpunit.
$argv_local = $GLOBALS['argv'] ?? [];
$key        = (int) array_search( '--testsuite', $argv_local, true );
$is_unit    = false;

// Check for --testsuite Unit (two separate args).
if ( $key && isset( $argv_local[ $key + 1 ] ) && 'Unit' === $argv_local[ $key + 1 ] ) {
	$is_unit = true;
}

// Check for --testsuite=Unit (single arg with equals).
foreach ( $argv_local as $arg ) {
	if ( '--testsuite=Unit' === $arg ) {
		$is_unit = true;
		break;
	}
}

if ( $is_unit ) {
	// Unit tests use Brain Monkey - no WordPress loaded.
	// Load plugin classes that can be tested without WordPress.
	require_once dirname( __DIR__ ) . '/src/class-acm-provider.php';
	require_once dirname( __DIR__ ) . '/src/class-ad-code-manager.php';
	require_once __DIR__ . '/Unit/TestCase.php';
	return;
}

// For integration tests, delegate to the Integration bootstrap.
require_once __DIR__ . '/Integration/bootstrap.php';
