<?php
/*
Plugin Name: Ad Code Manager
Plugin URI: http://automattic.com
Description: Easy ad code management 
Author: Daniel Bachhuber, Rinat Khaziev, Automattic
Version: 0.0
Author URI: http://automattic.com

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/
define( 'AD_CODE_MANAGER_VERSION', '0.0' );
define( 'AD_CODE_MANAGER_ROOT' , dirname(__FILE__) );
define( 'AD_CODE_MANAGER_FILE_PATH' , AD_CODE_MANAGER_ROOT . '/' . basename(__FILE__) );
define( 'AD_CODE_MANAGER_URL' , plugins_url( plugin_basename(dirname(__FILE__)).'/') );

class Ad_Code_Manager
{

	var $ad_codes = array();
	var $script_url_whitelist = array();
	
	/**
	 * Instantiate the plugin
	 *
	 * @since ??
	 */
	function __construct() {

		add_action( 'init', array( &$this, 'action_init' ) );
		add_action( 'admin_init', array( &$this, 'action_admin_init' ) );
		
	}

	/**
	 * Code to run on WordPress' 'init' hook
	 *
	 * @since ??
	 */
	function action_init() {

		// Allow new domains to be whitelisted


		// Ad tags are only run on the frontend
		if ( !is_admin() ) {
			require_once AD_CODE_MANAGER_ROOT . '/template-tags.php';
			add_action( 'acm_tag', array( &$this, 'action_acm_tag' ) );
		}
		
	}

	/**
	 * Code to run on WordPress' 'admin_init' hook
	 *
	 * @since ??
	 */
	function action_admin_init() {
		$this->register_scripts_and_styles();
		// @todo conditionally load the admin interface if that's enabled
		// The admin interface should be enabled by a filter and off by default
		// We'll need additional methods for:
		// - Displaying the interface
		// - Saving the data
		// - Loading the ad codes in the database and registering them
		// with the plugin using 
		
	}
	
	
	/**
	 * Register scripts and styles 
	 *
	 */
	function register_scripts_and_styles() {
		wp_enqueue_style( 'acm-jquery-ui-theme', AD_CODE_MANAGER_FILE_PATH . '/common/css/jquery-ui-1.8.17.custom.css');
		wp_enqueue_style( 'acm-jqgrid-css', AD_CODE_MANAGER_FILE_PATH . '/common/css/ui.jqgrid.css');
		wp_enqueue_script( 'acm-jqgrid-locale-en', AD_CODE_MANAGER_FILE_PATH . '/common/js/grid.locale-en.js'. array('jquery', 'jquery-ui' ) );
		wp_enqueue_script( 'acm-jqgrid', AD_CODE_MANAGER_FILE_PATH . '/common/js/jquery.jqGrid.min.js'. array('jquery', 'jquery-ui' ) );
		wp_enqueue_script( 'acm', AD_CODE_MANAGER_FILE_PATH . '/common/js/acm.js'. array('jquery', 'jquery-ui' ) );
		
	}

	/**
	 * Register an ad tag with the plugin so it can be used
	 * on the frontend of the site
	 *
	 * @since ???
	 *
	 * @param string $tag Ad tag for this instance of code
	 * @param string $script URL for ad code
	 * @param array $where WordPress-style conditionals for where this code should be displayed
	 * @param int $priority What priority this registration runs at
	 * @param array $url_vars Replace tokens in $script with these values
	 * @return bool|WP_Error $success Whether we were successful in registering the ad tag
	 */
	function register_ad_code( $tag, $script, $where = array(), $priority = 10, $url_vars = array() ) {

		// @todo Run $script aganist a whitelist to make sure it's a safe URL
		// @todo Sanitize all of the other input

		// @todo logic for saving the ad code to $this->ad_codes so it's available to $this->action_acm_tag()
	} 

	/**
	 * Display the ad code based on what's registered
	 * and complicated sorting logic
	 *
	 * @uses do_action( 'acm_tag, 'your_tag_id' )
	 */
	function action_acm_tag( $tag_id ) {
		
		// @todo possibly complicated logic for determining which
		// script is executed while factoring in:
		// - where it should be displayed
		// - priority against other ad codes

		// @todo Parse the script URL and replace with any $url_vars

		echo '<script type="text/javascript" src="' . esc_url( $code_url ) . '"></script>';

	}

}
global $ad_code_manager;
$ad_code_manager = new Ad_Code_Manager();