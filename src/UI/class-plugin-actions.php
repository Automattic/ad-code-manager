<?php
/**
 * Ad Code Manager plugins actions class
 *
 * @package Automattic\AdCodeManager
 * @since 0.6.0
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\UI;

use const Automattic\AdCodeManager\AD_CODE_MANAGER_FILE;

/**
 * User Interface changes for the plugins actions.
 *
 * @since 0.6.0
 */
final class Plugin_Actions {

	/**
	 * Register action and filter hook callbacks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_filter( 'plugin_action_links_' . plugin_basename( AD_CODE_MANAGER_FILE ), array( $this, 'add_plugin_meta_links' ) );
	}

	/**
	 * Adds a 'Settings' action link to the Plugins screen in WP admin.
	 *
	 * @param array $actions An array of plugin action links. By default, this can include 'activate',
	 *                       'deactivate', and 'delete'. With Multisite active this can also include
	 *                       'network_active' and 'network_only' items.
	 * @return array
	 */
	public function add_plugin_meta_links( array $actions ): array {
		$actions['settings'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_admin_url( null, 'options-general.php?page=ad-code-manager' ) ),
			esc_html__( 'Settings', 'ad-code-manager' )
		);

		return $actions;
	}
}
