<?php
/**
 * UI Tests for the plugin actions
 *
 * @package Automattic\AdCodeManager\Tests\UI
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration\UI;

use Automattic\AdCodeManager\Tests\Integration\TestCase;
use Automattic\AdCodeManager\UI\Plugin_Actions;

use const Automattic\AdCodeManager\AD_CODE_MANAGER_FILE;

/**
 * UI Tests for the plugin screen.
 */
final class PluginActionsTest extends TestCase {
	/**
	 * Check that plugins screen will add a hook to change the plugin action links.
	 *
	 * @covers \Automattic\AdCodeManager\UI\Plugin_Actions::run
	 * @group ui
	 */
	public function test_plugins_screen_has_filter_to_add_a_settings_action_link(): void {
		$plugins_screen = new Plugin_Actions();
		$plugins_screen->run();

		self::assertNotFalse( has_filter( 'plugin_action_links_' . plugin_basename( AD_CODE_MANAGER_FILE ), array( $plugins_screen, 'add_plugin_meta_links' ) ) );
	}

	/**
	 * Check that plugins screen will add a hook to change the plugin action links.
	 *
	 * @covers Automattic\AdCodeManager\UI\Plugin_Actions::run
	 * @covers Automattic\AdCodeManager\UI\Plugin_Actions::add_plugin_meta_links
	 * @group ui
	 */
	public function test_plugins_screen_adds_a_settings_action_link(): void {
		$actions = array();
		$actions = ( new Plugin_Actions() )->add_plugin_meta_links( $actions );

		self::assertCount( 1, $actions );
	}
}
