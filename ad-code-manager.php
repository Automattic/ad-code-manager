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
	var $whitelisted_conditionals_titles = array();
	var $output_html;
	var $output_tokens = array();
	var $title = 'Ad Code Manager';
	var $post_type = 'acm-code';
	var $plugin_slug = 'ad-code-manager';
	var $post_type_labels ;
	var $logical_operator;
	var $ad_tag_ids;

	/**
	 * Instantiate the plugin
	 *
	 * @since 0.1
	 */
	function __construct() {
		add_action('wp_ajax_acm_ajax_handler', array( &$this, 'ajax_handler' ) );
		add_action( 'init', array( &$this, 'action_init' ) );
		add_action( 'admin_init', array( &$this, 'action_admin_init' ) );

		// Incorporate the link to our admin menu
		add_action( 'admin_menu' , array( $this, 'action_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'register_scripts_and_styles' ) );
		add_action( 'admin_print_scripts', array( &$this, 'post_admin_header' ) );
	}

	/**
	 * Code to run on WordPress' 'init' hook
	 *
	 * @since 0.1
	 */
	function action_init() {
		$this->post_type_labels = array(
										'name' => __( 'DFP Ad Codes' ),
										'singular_name' => __( 'DFP Ad Codes' ),
										);

		// Allow new domains to be whitelisted
		$this->whitelisted_script_urls = apply_filters( 'acm_whitelisted_script_urls', $this->whitelisted_script_urls );

		// Allow other conditionals to be used
		$this->whitelisted_conditionals = array(
				'is_home',
				'is_front_page',
				'is_category',
				'has_category',
				'is_page',
				'is_tag',
				'has_tag',
			);
		$this->whitelisted_conditionals = apply_filters( 'acm_whitelisted_conditionals', $this->whitelisted_conditionals );
		$this->logical_operator = apply_filters( 'acm_logical_operator', 'OR'); //allow users to filter default logical operator

		// Set our default output HTML
		// This can be filtered in action_acm_tag()
		$this->output_html = '<script type="text/javascript" src="%url%"></script>';

		// Set our default tokens to replace
		// This can be filtered in action_acm_tag()
		$this->output_tokens = array();

		// These are common DFP tags
		$this->ad_tag_ids = array(
			array(
					'tag' => '728x90-atf',
					'url_vars' => array(
						'sz' => '728x90',
						'fold' => 'atf'
				)
			),
			array(
					'tag' => '728x90-btf',
					'url_vars' => array(
						'sz' => '728x90',
						'fold' => 'btf'
				)
			) ,
			array(
					'tag' => '300x250-atf',
					'url_vars' => array(
						'sz' => '300x250',
						'fold' => 'atf'
				)
			),
			array(
					'tag' => '300x250-btf',
					'url_vars' => array(
						'sz' => '300x250',
						'fold' => 'atf'
				)
			),
			array(
					'tag' => '160x600-atf',
					'url_vars' => array(
						'sz' => '160x600',
						'fold' => 'atf'
				)
			)
		);
		$this->ad_tag_ids = apply_filters( 'acm_ad_tag_ids', $this->ad_tag_ids );
		
		$this->register_acm_post_type();

		// Ad tags are only run on the frontend
		if ( !is_admin() ) {
			add_action( 'acm_tag', array( $this, 'action_acm_tag' ) );
			add_filter( 'acm_output_tokens', array( $this, 'filter_output_tokens' ), 5, 3 );
		}

		// Load all of our registered ad codes
		$this->register_ad_codes( $this->get_ad_codes() );
	}

	/**
	 * Register our custom post type to store ad codes
	 *
	 * @since 0.1
	 */
	function register_acm_post_type() {
		register_post_type( $this->post_type, array( 'labels' => $this->post_type_labels, 'public' => false ) );
	}

	/**
	 * Handles all admin ajax requests: getting, updating, creating and deleting
	 *
	 * @since 0.1
	 */
	function ajax_handler() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'acm_nonce' ) )
			return;
		switch( $_GET['acm-action'] ) {
			case 'datasource':
				$this->get_ad_codes_ajax();
				break;
			case 'datasource-conditionals':
				$this->get_conditionals_ajax();
				break;
			case 'edit':
				$this->ad_code_edit_actions();
				break;
			case 'edit-conditionals':
				$this->conditionals_edit_actions();
				break;
		}
		return;
	}

	/**
	 * Returns json encoded ad code
	 * This is the datasource for jqGRID
	 *
	 * @todo nonce?
	 */
	function get_ad_codes_ajax() {
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
			$ad_codes = $this->get_ad_codes() ;
			// prepare data in jqGrid specific format
			$pass = array();
			foreach ( $ad_codes as $ad_code ) {
				$pass[] = array(
					'id' => $ad_code[ 'post_id' ],
					'site_name' => $ad_code[ 'url_vars' ][ 'site_name' ] ,
					'zone1' => $ad_code[ 'url_vars' ][ 'zone1' ],
					'act' => '',
				);
			}
			$response->rows = $pass;

			$count = count( $response->rows );
			$total_pages = 1; // this should be $count / $_GET[ 'rows' ] // 'rows' is per page limit

			$response->page = isset( $_GET[ 'acm-grid-page' ] ) ? sanitize_key( $_GET[ 'acm-grid-page' ] ) : 1 ;
			$response->total = $total_pages;
			$response->records = $count;
			$this->print_json( $response );
		}
		return;
	}


	/**
	 * Get the ad codes stored in our custom post type
	 *
	 * @todo This is too DFP specific. Abstract it
	 */
	function get_ad_codes() {
		$ad_codes_formatted = array();
		$args = array(
			'post_type' => $this->post_type,
			'numberposts' => apply_filters( 'acm_ad_code_count', 50 ),
		);
		$ad_codes = get_posts( $args );
		foreach ( $ad_codes as $ad_code_cpt ) {
			$ad_codes_formatted[] = array(
				'conditionals' => $this->get_conditionals( $ad_code_cpt->ID ),
				'url_vars' => array(
					'site_name' => get_post_meta( $ad_code_cpt->ID, 'site_name', true ),
					'zone1' => get_post_meta( $ad_code_cpt->ID, 'zone1', true ),
				),
				'post_id' => $ad_code_cpt->ID
			);
		}
		return $ad_codes_formatted;
	}

	function get_conditionals_ajax() {
		if (  0 !== intval( $_GET[ 'id' ] ) ) {
			$conditionals = $this->get_conditionals( intval( $_GET[ 'id' ] ) );
			$response;
		foreach ( $conditionals as $index => $item )
				$response->rows[] = $item;
			$count = count( $response->rows );
			$total_pages = 1; // this should be $count / $_GET[ 'rows' ] // 'rows' is per page limit

			$response->page = isset( $_GET['acm-grid-page'] ) ? sanitize_key( $_GET['acm-grid-page'] ) : 1 ;
			$response->total = $total_pages;
			$response->records = $count;
			$this->print_json( $response );
		}
	}

	/**
	 * Get the conditional values for an ad code
	 */
	function get_conditionals( $ad_code_id ) {
		return get_post_meta( $ad_code_id, 'conditionals', true );
	}

	/**
	 * Handles AJAX Create, Update, Delete actions for Ad Codes
	 */
	function ad_code_edit_actions() {
		// Noncing happens in $this->ajax_handler()
		if ( ! empty( $_POST ) ) {
			 //this is jqGrid param
			switch ( $_POST[ 'oper' ] ) {
				case 'add':
					$this->create_ad_code( $_POST );
					break;
				case 'edit':
					$this->edit_ad_code( intval( $_GET[ 'id' ] ), $_POST );
					break;
				case 'del':
					$this->delete_ad_code( intval( $_POST[ 'id' ] ) );
					break;
			}
			exit; // exit, jqGrid sends another request to fetch new data
		}
		return;
	}

	function conditionals_edit_actions() {
		if (  ! empty( $_POST ) ) {
			switch ( $_POST[ 'oper' ] ) {
				case 'add':
					$result = $this->create_conditional( intval( $_GET['id'] ), $_POST );
					break;
				case 'edit':
					$result = $this->edit_conditional( intval( $_GET['id'] ), $_POST, true );
					break;
				case 'del':
					// That's confusing: $_GET['id'] refers to CPT ID, $_POST['id'] refers to indices that should be
					// removed from array of conditionals
					$result = $this->delete_conditional( intval( $_GET['id'] ), $_POST[ 'id' ], true );
					break;
			}
			exit($result);
		}
		return;
	}

	/**
	 * Create a new ad code in the database
	 *
	 * @uses register_ad_code()
	 *
	 * @todo validation / nonce
	 *
	 * @param array $ad_code
	 */
	function create_ad_code( $ad_code = array() ) {
		if ( $ad_code['site_name'] && $ad_code['zone1'] ) {
			$acm_post = array(
				'post_title' => $ad_code['site_name'] .'-' . $ad_code['zone1'],
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_type' => $this->post_type,
			);
			if ( ! is_wp_error( $acm_inserted_post_id = wp_insert_post( $acm_post, true ) ) ) {
				update_post_meta( $acm_inserted_post_id, 'site_name', $ad_code[ 'site_name' ] );
				update_post_meta( $acm_inserted_post_id, 'zone1', $ad_code[ 'zone1' ] );
			}
		}
		return;
	}

	/**
	 * Update an existing ad code
	 */
	function edit_ad_code( $ad_code_id, $ad_code = array() ) {
		if ( 0 !== $ad_code_id && $ad_code['site_name'] && $ad_code['zone1'] ) {
			update_post_meta( $ad_code_id, 'site_name', $ad_code['site_name'] );
			update_post_meta( $ad_code_id, 'zone1', $ad_code['zone1'] );
		}
		return;
	}

	/**
	 * Delete an existing ad code
	 */
	function delete_ad_code( $ad_code_id ) {
		if ( 0 !== $ad_code_id )
			wp_delete_post( $ad_code_id , true ); //force delete post
		return;
	}
	/**
	 * Create conditional
	 *
	 * @param int $ad_code_id id of our CPT post
	 * @param array $conditional to add
	 *
	 * @return void ???
	 */
	function create_conditional( $ad_code_id, $conditional ) {
		if ( 0 !== $ad_code_id && !empty( $conditional ) ) {
			$existing_conditionals =  get_post_meta( $ad_code_id, 'conditionals', true );
			if ( ! is_array( $existing_conditionals ) ) {
				$existing_conditionals = array();
			}
			$existing_conditionals[] = array(
				'function' => $conditional[ 'function' ],
				'arguments' => (array) $conditional[ 'arguments' ], // @todo explode
			);
			update_post_meta( $ad_code_id, 'conditionals', $existing_conditionals );
		}
		return;
	}

	/**
	 * Update conditional
	 *
	 * @param int $ad_code_id id of our CPT post
	 * @param array $conditional
	 *
	 */
	function edit_conditional( $ad_code_id, $conditional, $from_ajax = false ) {
		if ( 0 !== $ad_code_id && !empty( $conditional ) ) {
			$existing_conditionals = (array) get_post_meta( $ad_code_id, 'conditionals', true );
			if ( $from_ajax && isset( $conditional ['id'] ) ) { // jqGrid starts with one, PHP starts with 0
					$conditional['id']--;
			}
			foreach ( $existing_conditionals as $conditional_index => $existing_conditional ) {
				// $id is not an actual unique ID, but rather index of conditional in array of them
				if ( isset( $conditional['id'] ) && $conditional['id'] === $conditional_index ) {
					$existing_conditionals[ $conditional_index ] = array(
						'function' => $conditional[ 'function' ],
						'arguments' => (array) $conditional[ 'arguments' ],
					);
				}
			}
			return update_post_meta( $ad_code_id, 'conditionals', array_values($existing_conditionals) );
		}
		return;
	}

	/**
	 * This is a bit tricky as we really don't use any ID for conditionals
	 * To remove conditional we need to specify array index
	 *
	 * @param int $ad_code_id
	 * @param string $conditional_indices string of comma separated indices
	 */
	function delete_conditional( $ad_code_id, $conditional_indices = '', $from_ajax = false ) {
		if ( 0 !== $ad_code_id ) {
			$existing_conditionals = get_post_meta( $ad_code_id, 'conditionals', true );
			$ids_to_delete = explode(',', $conditional_indices ); //
			foreach ($ids_to_delete as $index_to_delete ) {
				if ( $from_ajax ) { // jqGrid starts with one, PHP starts with 0
					$index_to_delete--;
				}
				unset( $existing_conditionals[ $index_to_delete ] );
			}
			update_post_meta( $ad_code_id, 'conditionals', array_values( $existing_conditionals ) ); //array_values to keep indices consistent
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

		$conditionals_parsed = array();
		foreach ( $this->whitelisted_conditionals as $conditional )
				$conditionals_parsed[] = $conditional . ':' . ucfirst( str_replace('_', ' ', $conditional ) );
		?>
		<script type="text/javascript">
			var acm_url = '<?php echo esc_js( admin_url( 'admin.php?page=' . $this->plugin_slug ) )  ?>';
			var acm_conditionals = '<?php echo esc_js( implode( ';', $conditionals_parsed ) )?>';
			var acm_ajax_nonce = '<?php echo esc_js( wp_create_nonce('acm_nonce') ) ?>';
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
	 * Print the admin interface for managing the ad codes
	 *
	 * @todo remove html to views
	 */
	function admin_view_controller() {
	?>
	<h2>Ad Code Manager</h2>
	<table id="acm-codes-list"></table>
	<div id="acm-codes-pager"></div>

	<table id="acm-codes-conditionals-list"></table>
	<div id="acm-codes-conditionals-pager"></div>
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
	 * @since 0.1
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
	 * @since 0.1
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

			foreach ( (array)$this->ad_tag_ids as $default_tag ) {
				// May be we should add plugin setting for default url. For now just apply the filter which should return default url if $ad_code['url'] is empty
				$this->register_ad_code( $default_tag['tag'], apply_filters( 'acm_empty_url', $ad_code['url'] ), $ad_code['conditionals'], array_merge( $default_tag['url_vars'], $ad_code['url_vars'] ) );
			}
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
					$result = call_user_func_array( $cond_func, apply_filters( 'acm_conditional_args', $cond_args, $cond_func  ) );
				else 
					$result = call_user_func( $cond_func );
				

				// If our results don't match what we need, don't include this ad code
				if ( $cond_result !== $result )
					$include = false;
				else
					$include = true;

				//
				// If we have matching conditional and $this->logical_operator equals OR just break from the loop and do not try to evaluate others
				if ( $include && $this->logical_operator == 'OR' )
					break;

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

		// Parse the output and replace any tokens we have left. But first, load the script URL
		$output_html = str_replace( '%url%', $code_to_display['url'], $output_html );
		$output_tokens = apply_filters( 'acm_output_tokens', $this->output_tokens, $tag_id, $code_to_display );
		
		foreach( (array)$output_tokens as $token => $val ) {
			$output_html = str_replace( $token, $val, $output_html );
		}

		// Print the ad code
		echo $output_html;
	}

	/**
	 * Filter the output tokens used in $this->action_acm_tag to include our URL vars
	 *
	 * @since 0.1
	 *
	 * @return array $output Placeholder tokens to be replaced with their values
	 */
	function filter_output_tokens( $output_tokens, $tag_id, $code_to_display ) {
		if ( !isset( $code_to_display['url_vars'] ) || !is_array( $code_to_display['url_vars'] ) )
			return $output_tokens;

		foreach( $code_to_display['url_vars'] as $url_var => $val ) {
			$new_key = '%' . $url_var . '%';
			$output_tokens[$new_key] = $val;
		}
		
		return $output_tokens;
	}

}
global $ad_code_manager;
$ad_code_manager = new Ad_Code_Manager();