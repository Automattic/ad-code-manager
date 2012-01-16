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
define( 'AD_CODE_MANAGER_ROOT' , dirname( __FILE__ ) );
define( 'AD_CODE_MANAGER_FILE_PATH' , AD_CODE_MANAGER_ROOT . '/' . basename( __FILE__ ) );
define( 'AD_CODE_MANAGER_URL' , plugins_url( plugin_basename( dirname( __FILE__ ) ) . '/' ) );

class Ad_Code_Manager
{

	var $ad_codes = array();
	var $whitelisted_script_urls = array();
	var $whitelisted_conditionals = array();
	var $output_html;
	var $output_tokens = array();
	var $title = 'Ad Code Manager';
	var $post_type = 'acm-code';
	var $plugin_slug = 'ad-code-manager';
	var $post_type_labels ;
	/**
	 * Instantiate the plugin
	 *
	 * @since ??
	 */
	function __construct() {
		// @todo refactor TODO
		add_action( 'init', array( &$this, 'action_init' ) );
		add_action( 'admin_init', array( &$this, 'action_admin_init' ) );

		// Incorporate the link to our admin menu
		add_action( 'admin_menu' , array( $this, 'action_admin_menu' ) );

		add_action( 'admin_init', array( &$this, 'get_ad_codes' ) );
		add_action( 'admin_init', array( &$this, 'ad_code_edit_actions' ) );
		add_action( 'admin_init', array( &$this, 'conditions_edit_actions' ) );
		add_action( 'admin_init', array( &$this, 'get_conditions' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_and_styles' ) );
		add_action( 'admin_print_scripts', array( &$this, 'post_admin_header' ) );

		$this->post_type_labels = array(
										'name' => __( 'DFP Ad Codes' ),
										'singular_name' => __( 'DFP Ad Codes' ),
										);
	}

	/**
	 * Code to run on WordPress' 'init' hook
	 *
	 * @since ??
	 */
	function action_init() {

		// Allow new domains to be whitelisted
		$this->whitelisted_script_urls = apply_filters( 'acm_whitelisted_script_urls', $this->whitelisted_script_urls );

		// Allow other conditionals to be used
		$this->whitelisted_conditionals = array(
				'is_home',
				'is_front_page',
				'is_category',
				'has_category',
			);
		$this->whitelisted_conditionals = apply_filters( 'acm_whitelisted_conditionals', $this->whitelisted_conditionals );

		// Set our default output HTML
		// This can be filtered in action_acm_tag()
		$this->output_html = '<script type="text/javascript" src="%url%"></script>';

		// Set our default tokens to replace
		// This can be filtered in action_acm_tag()
		$this->output_tokens = array(
				'%url%',
			);

		$this->register_acm_post_type();

		// Ad tags are only run on the frontend
		if ( !is_admin() ) {
			require_once AD_CODE_MANAGER_ROOT . '/template-tags.php';
			add_action( 'acm_tag', array( $this, 'action_acm_tag' ) );

			// @todo get all of the ad codes and register them with register_ad_code()
		}

	}

	/**
	 * Code to run on WordPress' 'admin_init' hook
	 *
	 * @since ??
	 */
	function action_admin_init() {
		// @todo conditionally load the admin interface if that's enabled
		// The admin interface should be enabled by a filter and off by default
		// We'll need additional methods for:
		// - Displaying the interface
		// - Saving the data
		// - Loading the ad codes in the database and registering them
		// with the plugin using

	}

	/**
	 * Register our custom post type to store ad codes
	 *
	 * @since ??
	 */
	function register_acm_post_type() {
		register_post_type( $this->post_type, array( 'labels' => $this->post_type_labels, 'public' => false ) );
	}

	/**
	 * Returns json encoded ad code
	 * This is the datasource for jqGRID
	 *
	 * @todo nonce?
	 * @todo actual logic for getting ad codes from our custom post type
	 */
	function get_ad_codes() {
		// These are params that should be managed via UI
		/**
		 * NB!
		 * $response is an object with following properties
		 * $response->page = current page
		 * $response->total = total pages
		 * $response->record = count of rows
		 * $response->rows = nested array of assoc arrays @see $model
		 */
		$response;
		if ( isset( $_GET[ 'acm-action' ] ) && $_GET[ 'acm-action'] == 'datasource' ) {
			$ad_codes = get_posts( array( 'post_type' => $this->post_type ) );
			// prepare data in jqGrid specific format
			$pass = array();
			foreach ( $ad_codes as $ad_code ) {
				$pass[] = array(
					'id' => $ad_code->ID,
					'site_name' => get_post_meta( $ad_code->ID, 'site_name', true ),
					'zone1' => get_post_meta( $ad_code->ID, 'zone1', true ),
					'act' => '',
				);
			}
			$response->rows = $pass;

			$count = count( $response->rows );
			$total_pages = 1; // this should be $count / $_GET[ 'rows' ] // 'rows' is per page limit

			$response->page = isset( $_GET[ 'acm-grid-page' ] ) ? $_GET[ 'acm-grid-page' ] : 1 ;
			$response->total = $total_pages;
			$response->records = $count;
			$this->print_json( $response );
		}
		return;
	}

	function get_conditions() {
		if ( isset( $_GET[ 'acm-action' ] ) && $_GET[ 'acm-action'] == 'datasource-conditions' & 0 !== intval( $_GET[ 'id' ] ) ) {
			$response;
			$conditions = get_post_meta( intval( $_GET[ 'id' ] ), 'conditions', true );
			foreach ($conditions as $index => $item )
				$response->rows[] = $item;
			$count = count( $response->rows );
			$total_pages = 1; // this should be $count / $_GET[ 'rows' ] // 'rows' is per page limit

			$response->page = isset( $_GET['acm-grid-page'] ) ? $_GET['acm-grid-page'] : 1 ;
			$response->total = $total_pages;
			$response->records = $count;
			$this->print_json( $response );
		}
		return;
	}

	/**
	 * Handles Create, Update, Delete actions
	 *
	 * @todo nonce + jqGrid?
	 */
	function ad_code_edit_actions() {
		if ( isset( $_GET[ 'acm-action' ] ) && $_GET[ 'acm-action'] == 'edit' && ! empty( $_POST ) ) {
			 //this is jqGrid param
			switch ( $_POST[ 'oper' ] ) {
				case 'add':
					$this->create_ad_code();
					break;
				case 'edit':
					$this->edit_ad_code();
					break;
				case 'del':
					$this->delete_ad_code();
					break;
			}
			exit; // exit, jqGrid sends another request to fetch new data
		}
		return;
	}

	function conditions_edit_actions() {
		if ( isset( $_GET[ 'acm-action' ] ) && $_GET[ 'acm-action'] == 'edit-conditions' && ! empty( $_POST ) ) {
			switch ( $_POST[ 'oper' ] ) {
				case 'add':
					$this->create_condition();
					break;
				case 'edit':
					$this->edit_condition();
					break;
				case 'del':
					$this->delete_condition();
					break;
			}
			exit;
		}
		return;
	}

	/**
	 * @uses register_ad_code()
	 * @todo validation / nonce
	 */
	function create_ad_code() {
		if ( $_POST['site_name'] && $_POST['zone1'] ) {
			$acm_post = array(
							  'post_title' => $_POST['site_name'] .'-' . $_POST['zone1'],
							  'post_status' => 'publish',
							  'comment_status' => 'closed',
							  'ping_status' => 'closed',
							  'post_type' => $this->post_type,
							  );
			if ( ! is_wp_error( $acm_inserted_post_id = wp_insert_post( $acm_post, true ) ) ) {
				update_post_meta( $acm_inserted_post_id, 'site_name', $_POST[ 'site_name' ] );
				update_post_meta( $acm_inserted_post_id, 'zone1', $_POST[ 'zone1' ] );
			}
		}
		return;
	}

	function edit_ad_code() {
		if ( isset($_POST['id'] ) && $_POST['site_name'] && $_POST['zone1'] ) {
			$acm_inserted_post_id = intval( $_POST[ 'id' ] );
			update_post_meta( $acm_inserted_post_id, 'site_name', $_POST['site_name'] );
			update_post_meta( $acm_inserted_post_id, 'zone1', $_POST['zone1'] );
		}
		return;
	}

	function delete_ad_code() {
		if ( isset( $_POST['id'] ) )
			wp_delete_post( intval( $_POST[ 'id' ] ) , true ); //force delete post
		return;
	}

	function create_condition() {
		if ( isset( $_GET['id'] ) && ! empty( $_POST ) ) {
			$existing_conditions = get_post_meta( intval( $_GET[ 'id' ] ), 'conditions', true );
			if ( ! is_array( $existing_conditions ) ) {
				$existing_conditions = array();
			}
			$existing_conditions[] = array(
											'condition' => $_POST[ 'condition' ],
											'value' => $_POST[ 'value' ],
											'priority' => intval( $_POST[ 'priority' ] ),
										   );
			update_post_meta( intval( $_GET[ 'id' ] ), 'conditions', $existing_conditions );
		}
		return;
	}

	function edit_condition() {
		if ( isset( $_GET['id'] ) && !empty( $_POST ) ) {
			$existing_conditions = (array) get_post_meta( intval( $_GET[ 'id' ] ), 'conditions', true );

			foreach ( $existing_conditions as $index => $condition ) {
				if ( $_POST[ 'condition' ] == $condition[ 'condition' ] ) {
					$existing_conditions[ $index ] = array(
								  'condition' => $_POST[ 'condition' ],
								  'value' => $_POST[ 'value' ],
								  'priority' => intval( $_POST[ 'priority' ] ),
								  );
				}
			}
			update_post_meta( intval( $_GET[ 'id' ] ), 'conditions', $existing_conditions );
		}
		return;
	}

	function delete_condition() {
		if ( isset( $_GET['id'] ) && !empty( $_POST ) ) {
			$existing_conditions = get_post_meta( intval( $_GET[ 'id' ] ), 'conditions', true );
			$ids_to_delete = explode(',', $_POST[ 'id' ] ); //
			foreach ($ids_to_delete as $index )
				unset( $existing_conditions[ --$index ] ); // jqGrid starts with one, PHP starts with 0
			update_post_meta( intval( $_GET[ 'id' ] ), 'conditions', array_values( $existing_conditions ) ); //array_values to keep indices consistent
		}
		return;
	}

	/**
	 * encode as json any given $data
	 */
	function print_json( $data = array() ) {
		header( 'Content-type: application/json;' );
		echo json_encode( $data );
		exit;
	}

	/**
	 * Print our vars as JS
	 */
	function post_admin_header() {

		if ( !isset( $_GET['page'] ) || $_GET['page'] != $this->plugin_slug )
			return;

		// @todo This needs to reflect $this->whitelisted_conditionals;
		$conditions = apply_filters(
									'acm_conditions',
									array(
										'is_category' => 'Is Category?',
										'is_page' => 'Is Page?',
										'has_category' => 'Has Category?',
										'is_tag' => 'Is Tag?',
										'has_tag' => 'Has Tag?',
										)
									);
		$conditions_parsed = array();
		foreach ( $conditions as $ck => $cv )
			$conditions_parsed[] = "$ck:$cv";
		?>
		<script type="text/javascript">
			var acm_url = '<?php echo esc_js( admin_url( 'admin.php?page=' . $this->plugin_slug ) )  ?>';
			var acm_conditions = '<?php echo esc_js( implode( ';', $conditions_parsed ) )?>';
		</script>
		<?php
	}

	/**
	 * Hook in our submenu page to the navigation
	 */
	function action_admin_menu() {
		add_submenu_page( 'tools.php', $this->title, $this->title, apply_filters( 'acm_manage_ads_cap', 'manage_options' ), $this->plugin_slug, array( &$this, 'admin_view_controller' ) );
	}

	/**
	 * @todo remove html to views
	 */
	function admin_view_controller() {
	?>
	<h2>Ad Code Manager</h2>
	<table id="acm-codes-list"></table>
	<div id="acm-codes-pager"></div>

	<table id="acm-codes-conditions-list"></table>
	<div id="acm-codes-conditions-pager"></div>
	<?php
	}

	/**
	 * Register scripts and styles
	 *
	 */
	function register_scripts_and_styles() {
		global $pagenow;

		// Only load this on the proper page
		if ( 'tools.php' != $pagenow || !isset( $_GET['page'] ) || $_GET['page'] != $this->plugin_slug )
			return;

		wp_enqueue_style( 'acm-jquery-ui-theme', AD_CODE_MANAGER_URL . '/common/css/jquery-ui-1.8.17.custom.css' );
		wp_enqueue_style( 'acm-jqgrid', AD_CODE_MANAGER_URL . '/common/css/ui.jqgrid.css' );
		wp_enqueue_script( 'acm-jqgrid-locale-en', AD_CODE_MANAGER_URL . '/common/js/grid.locale-en.js', array( 'jquery', 'jquery-ui-core' ) );
		wp_enqueue_script( 'acm-jqgrid', AD_CODE_MANAGER_URL . '/common/js/jquery.jqGrid.min.js', array( 'jquery', 'jquery-ui-core' ) );
		wp_enqueue_script( 'acm', AD_CODE_MANAGER_URL . '/common/js/acm.js', array( 'jquery', 'jquery-ui-core' ) );
	}

	/**
	 * Register an ad tag with the plugin so it can be used
	 * on the frontend of the site
	 *
	 * @since ???
	 *
	 * @param string $tag Ad tag for this instance of code
	 * @param string $url Script URL for ad code
	 * @param array $conditionals WordPress-style conditionals for where this code should be displayed
	 * @param int $priority What priority this registration runs at
	 * @param array $url_vars Replace tokens in $script with these values
	 * @return bool|WP_Error $success Whether we were successful in registering the ad tag
	 */
	function register_ad_code( $tag, $url, $conditionals = array(), $url_vars = array() ) {

		// @todo Run $url aganist a whitelist to make sure it's a safe URL

		// @todo Sanitize the conditionals against our possible set of conditionals so that users
		// can't just run arbitrary functions

		// @todo Sanitize all of the other input

		// Save the ad code to our set of ad codes
		$this->ad_codes[$tag][] = array(
				'url' => $url,
				'conditionals' => $conditionals,
				'url_vars' => $url_vars,
			);
	}

	/**
	 * Register an array of ad tags with the plugin
	 *
	 * @since ???
	 *
	 * @param array $ad_codes An array of ad tags
	 */
	function register_ad_codes( $ad_codes = array() ) {

		foreach( (array)$ad_codes as $key => $ad_code ) {
			$default = array(
					'tag' => '',
					'url' => '',
					'conditionals' => array(),
					'url_vars' => array(),
				);
			$ad_code = array_merge( $default, $ad_code );
			$this->register_ad_code( $ad_code['tag'], $ad_code['url'], $ad_code['conditionals'], $ad_code['url_vars'] );
		}
	}

	/**
	 * Display the ad code based on what's registered
	 * and complicated sorting logic
	 *
	 * @uses do_action( 'acm_tag, 'your_tag_id' )
	 *
	 * @todo implement prioritization. currently, we just pull the first registered ad meeting criteria
	 *
	 * @param string $tag_id Unique ID for the ad tag
	 */
	function action_acm_tag( $tag_id ) {

		// If there aren't any ad codes, it's not worth it for us to do anything.
		if ( !isset( $this->ad_codes[$tag_id] ) )
			return;

		// Run our ad codes through all of the conditionals to make sure we should
		// be displaying it
		$display_codes = array();
		foreach( (array)$this->ad_codes[$tag_id] as $ad_code ) {
			
			// If the ad code doesn't have any conditionals,
			// we should add it to the display list
			if ( empty( $ad_code['conditionals'] ) ) {
				$display_codes[] = $ad_code;
				continue;
			}
				
			$include = true;
			foreach( $ad_code['conditionals'] as $conditional ) {

				// If the conditional was passed as an array, then we have a complex rule
				// Otherwise, we have a function name and expect rue
				if ( is_array( $conditional ) ) {
					$cond_func = $conditional['function'];
					if ( !empty( $conditional['arguments'] ) )
						$cond_args = $conditional['arguments'];
					else
						$cond_args = array();
					if ( isset( $conditional['result'] ) )
						$cond_result = $conditional['result'];
					else
						$cond_result = true;
				} else {
					$cond_func = $conditional;
					$cond_args = array();
					$cond_result = true;
				}

				// Special trick: include '!' in front of the function name to reverse the result
				if ( 0 === strpos( $cond_func, '!' ) ) {
					$cond_func = ltrim( $cond_func, '!' );
					$cond_result = false;
				}

				// Don't run the conditional if the conditional function doesn't exist or
				// isn't in our whitelist
				if ( !function_exists( $cond_func ) || !in_array( $cond_func, $this->whitelisted_conditionals ) )
					continue;

				// Run our conditional and use any arguments that were passed
				if ( !empty( $cond_args ) )
					$result = call_user_func_array( $cond_func, (array)$cond_args );
				else
					$result = call_user_func( $cond_func );
				
				// If our results don't match what we need, don't include this ad code
				if ( $cond_result !== $result )
					$include = false;
			}

			// If we're supposed to include the ad code even after we've run the conditionals,
			// let's do it
			if ( $include )
				$display_codes[] = $ad_code;

		}

		// Don't do anything if we've ended up with no ad codes
		if ( empty( $display_codes ) )
			return;

		// @todo possibly complicated logic for determining which
		// script is executed while factoring in:
		// - priority against other ad codes

		$code_to_display = $display_codes[0];

		// Allow the user to filter the basic output HTML, possibly based on tag_id
		// This can be useful if they need different script tags based 
		$output_html = apply_filters( 'acm_output_html', $this->output_html, $tag_id );

		// Parse the output and replace any tokens we have left
		$output_tokens = apply_filters( 'acm_output_tokens', $this->output_tokens, $tag_id );
		foreach( (array)$output_tokens as $token ) {
			// Strip away the token chars to get the key
			$key = trim( $token, '%' );
			if ( $key == 'url' ) {
				$output_html = str_replace( $token, $code_to_display['url'], $output_html );
				continue;
			} else {
				if ( !array_key_exists( $code_to_display['url_vars'][$key] ) )
					continue;
				$output_html = str_replace( $token, $code_to_display['url_vars'][$key], $output_html );	
			}
		}
		// Print the ad code
		echo $output_html;
	}

}
global $ad_code_manager;
$ad_code_manager = new Ad_Code_Manager();