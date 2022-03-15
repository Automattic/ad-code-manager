<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Automattic\AdCodeManager
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration {

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

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['wp_tests_options'] = array(
		'active_plugins' => array( 'ad-code-manager/ad-code-manager.php' ),
	);

	require_once dirname( __DIR__ ) . '/../vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

	/*
	 * Load WordPress, which will load the Composer autoload file, and load the MockObject autoloader after that.
	 */
	WPIntegration\bootstrap_it();

	if ( ! defined( 'WP_PLUGIN_DIR' ) || file_exists( WP_PLUGIN_DIR . '/ad-code-manager/ad-code-manager.php' ) === false ) {
		echo PHP_EOL, 'ERROR: Please check whether the WP_PLUGIN_DIR environment variable is set and set to the correct value. The unit test suite won\'t be able to run without it.', PHP_EOL;
		exit( 1 );
	}

	// Include the custom test case.
	require_once __DIR__ . '/TestCase.php';
}

// Plugin root file is not included during tests, so define the namespaced constants here.
namespace Automattic\AdCodeManager {
	const AD_CODE_MANAGER_VERSION = '123456.78.9';
	const AD_CODE_MANAGER_FILE    = __DIR__ . '/../../ad-code-manager.php';
}
