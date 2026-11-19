<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Automattic\AdCodeManager
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration;

use Yoast\WPTestUtils\WPIntegration;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if ( ! $_tests_dir ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
	putenv( 'WP_TESTS_DIR=' . $_tests_dir );
}

if ( getenv( 'WP_PLUGIN_DIR' ) !== false ) {
	define( 'WP_PLUGIN_DIR', getenv( 'WP_PLUGIN_DIR' ) );
} else {
	define( 'WP_PLUGIN_DIR', dirname( __DIR__, 3 ) );
}

// The plugin's main file, relative to the plugins directory. The checkout's directory name
// varies (a git worktree has its own name), so don't assume it's ad-code-manager.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$_plugin_file = basename( dirname( __DIR__, 2 ) ) . '/ad-code-manager.php';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( $_plugin_file ),
);

require_once dirname( __DIR__ ) . '/../vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

/*
 * Load WordPress, which will load the Composer autoload file, and load the MockObject autoloader after that.
 */
WPIntegration\bootstrap_it();

if ( ! defined( 'WP_PLUGIN_DIR' ) || file_exists( WP_PLUGIN_DIR . '/' . $_plugin_file ) === false ) {
	echo PHP_EOL, 'ERROR: Please check whether the WP_PLUGIN_DIR environment variable is set and set to the correct value. The unit test suite won\'t be able to run without it.', PHP_EOL;
	exit( 1 );
}

// Include the custom test case.
require_once __DIR__ . '/TestCase.php';
