<?php
/**
 * Main Ad Code Manager class
 *
 * @package Automattic\AdCodeManager
 * @since 0.6.0
 */

declare(strict_types=1);

// namespace Automattic\AdCodeManager;

use const Automattic\AdCodeManager\AD_CODE_MANAGER_FILE;
use const Automattic\AdCodeManager\AD_CODE_MANAGER_VERSION;

class Ad_Code_Manager {

	public $ad_codes                 = array();
	public $whitelisted_conditionals = array();
	public $post_type                = 'acm-code';
	public $plugin_slug              = 'ad-code-manager';
	public $manage_ads_cap           = 'manage_options';
	public $logical_operator;
	public $ad_tag_ids;
	public $providers;
	public $current_provider_slug;
	public $current_provider;
	public $wp_list_table;

	/**
	 * Instantiate the plugin
	 *
	 * @since 0.1
	 */
	function run() {
		add_action( 'init', array( $this, 'action_load_providers' ) );
		add_action( 'init', array( $this, 'action_init' ) );

		// Incorporate the link to our admin menu
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'wp_ajax_acm_admin_action', array( $this, 'handle_admin_action' ) );

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_shortcode( 'acm-tag', array( $this, 'shortcode' ) );
		// Workaround for PHP 5.4 warning: Creating default object from empty value in
		$this->providers = new stdClass();
	}

	/**
	 * Load all available ad providers
	 *
	 * Also, set selected as ACM_Provider $current_provider which holds all necessary configuration properties.
	 */
	function action_load_providers() {
		$providers_dir   = dirname( AD_CODE_MANAGER_FILE ) . '/src/Providers/';
		$providers_files = array_diff( scandir( $providers_dir ), array( '..', '.' ) );

		foreach ( $providers_files as $providers_file ) {
			include_once $providers_dir . '/' . $providers_file;
			$module_dir = str_replace( array( 'class-', '.php' ), '', $providers_file );

			$tmp              = explode( '-', $module_dir );
			$class_name       = '';
			$slug_name        = '';
			$table_class_name = '';
			foreach ( $tmp as $word ) {
				if ( $word === 'class' ) {
					continue;
				}
				$class_name .= ucfirst( $word ) . '_';
				$slug_name  .= $word . '_';
			}
			$table_class_name = $class_name . 'ACM_WP_List_Table';
			$class_name      .= 'ACM_Provider';
			$slug_name        = rtrim( $slug_name, '_' );

			// Store class names, but don't instantiate
			// We don't need them all at once
			if ( class_exists( $class_name ) ) {
				$this->providers->$slug_name = array(
					'provider' => $class_name,
					'table'    => $table_class_name,
				);
			}
		}

		/**
		 * Configuration filter: acm_register_provider_slug
		 *
		 * We've already gathered a list of default Providers by scanning the ACM plugin
		 * directory for classes that we can use. To add a provider already included via
		 * a different directory, the following filter is provided.
		 */
		$this->providers = apply_filters( 'acm_register_provider_slug', $this->providers );

		/**
		 * Configuration filter: acm_provider_slug
		 *
		 * By default we use doubleclick-for-publishers provider
		 * To switch to a different ad provider use this filter
		 */

		$this->current_provider_slug = apply_filters( 'acm_provider_slug', $this->get_option( 'provider' ) );

		// Instantiate one that we need
		if ( isset( $this->providers->{$this->current_provider_slug} ) ) {
			$current_provider_class = $this->providers->{$this->current_provider_slug}['provider'];
			$this->current_provider = new $current_provider_class();
		}

		// Nothing to do without a provider
		if ( ! is_object( $this->current_provider ) ) {
			return;
		}
		/**
		 * Configuration filter: acm_whitelisted_script_urls
		 * A security filter to whitelist which ad code script URLs can be added in the admin
		 */
		$this->current_provider->whitelisted_script_urls = apply_filters( 'acm_whitelisted_script_urls', $this->current_provider->whitelisted_script_urls );
	}

	/**
	 * Code to run on WordPress' 'init' hook
	 *
	 * @since 0.1
	 */
	function action_init() {
		// Allow other conditionals to be used
		$this->whitelisted_conditionals = array(
			'is_home',
			'is_front_page',
			'is_category',
			'has_category',
			'is_single',
			'is_page',
			'is_tag',
			'has_tag',
		);
		/**
		 * Configuration filter: acm_whitelisted_conditionals
		 * Extend the list of usable conditional functions with your own awesome ones.
		 */
		$this->whitelisted_conditionals = apply_filters( 'acm_whitelisted_conditionals', $this->whitelisted_conditionals );
		// Allow users to filter default logical operator
		$this->logical_operator = apply_filters( 'acm_logical_operator', 'OR' );

		// Allow the ad management cap to be filtered if need be
		$this->manage_ads_cap = apply_filters( 'acm_manage_ads_cap', $this->manage_ads_cap );

		// Load default ad tags for provider
		$this->ad_tag_ids = $this->current_provider->ad_tag_ids;
		/**
		 * Configuration filter: acm_ad_tag_ids
		 * Extend set of default tag ids. Ad tag ids are used as a parameter
		 * for your template tag (e.g. do_action( 'acm_tag', 'my_top_leaderboard' ))
		 */
		$this->ad_tag_ids = apply_filters( 'acm_ad_tag_ids', $this->ad_tag_ids );

		/**
		 * Configuration filter: acm_ad_code_args
		 * Allow the ad code arguments to be filtered
		 * Useful if we need to dynamically change these arguments based on the above
		 */
		$this->current_provider->ad_code_args = apply_filters( 'acm_ad_code_args', $this->current_provider->ad_code_args );

		$this->register_acm_post_type();

		// Ad tags are only run on the frontend
		if ( ! is_admin() ) {
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
		$labels = array(
			'name'          => _x( 'Ad Codes', 'Post Type General Name', 'ad-code-manager' ),
			'singular_name' => _x( 'Ad Code', 'Post Type Singular Name', 'ad-code-manager' ),
		);
		$args = array(
				'labels'  => $labels,
				'public'  => false,
				'rewrite' => false,
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Get all ACM options
	 *
	 * @since 0.4
	 */
	function get_options() {
		$default_provider = 'doubleclick_for_publishers';
		// Make sure our default provider exists. Otherwise, the sky will fall on our head
		if ( ! isset( $this->providers->$default_provider ) ) {
			foreach ( $this->providers as $slug => $provider ) {
				$default_provider = $slug;
				break;
			}
		}

		$defaults = array(
			'provider' => $default_provider,
		);
		$options  = get_option( 'acm_options', array() );
		return array_merge( $defaults, $options );
	}

	/**
	 * Get an ACM option
	 *
	 * @since 0.4
	 */
	function get_option( $key ) {
		$options = $this->get_options();

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		} else {
			return false;
		}
	}

	/**
	 * Update an ACM option
	 *
	 * @since 0.4
	 */
	function update_options( $new_options ) {
		$options = $this->get_options();
		$options = array_merge( $options, $new_options );
		update_option( 'acm_options', $options );
	}

	/**
	 * Handle any Add, Edit, or Delete actions from the admin interface
	 * Hooks into admin ajax because it's the proper context for these sort of actions
	 *
	 * @since 0.2
	 */
	function handle_admin_action() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'acm-admin-action' ) ) {
			wp_die( esc_html__( 'Doing something fishy, eh?', 'ad-code-manager' ) );
		}

		if ( ! current_user_can( $this->manage_ads_cap ) ) {
			wp_die( esc_html__( 'You do not have the necessary permissions to perform this action', 'ad-code-manager' ) );
		}

		$method  = isset( $_REQUEST['method'] ) ? sanitize_text_field( $_REQUEST['method'] ) : '';
		$message = '';

		// Depending on the method we're performing, sanitize the requisite data and do it.
		switch ( $method ) {
			case 'add':
			case 'edit':
				$id           = ( isset( $_REQUEST['id'] ) ) ? absint( $_REQUEST['id'] ) : 0;
				$priority     = ( isset( $_REQUEST['priority'] ) ) ? absint( $_REQUEST['priority'] ) : 10;
				$operator     = ( isset( $_REQUEST['operator'] ) && in_array( $_REQUEST['operator'], array( 'AND', 'OR' ) ) ) ? sanitize_text_field( $_REQUEST['operator'] ) : $this->logical_operator;
				$ad_code_vals = array(
					'priority' => $priority,
					'operator' => $operator,
				);
				foreach ( $this->current_provider->ad_code_args as $arg ) {
					$ad_code_vals[ $arg['key'] ] = sanitize_text_field( $_REQUEST['acm-column'][ $arg['key'] ] ?? '' );
				}
				if ( 'add' === $method ) {
					$id = $this->create_ad_code( $ad_code_vals );
				} else {
					$id = $this->edit_ad_code( $id, $ad_code_vals );
				}
				if ( is_wp_error( $id ) ) {
					// We can die with an error if this is an edit/ajax request
					if ( isset( $id->errors['edit-error'][0] ) ) {
						die( '<div class="error">' . esc_html( $id->errors['edit-error'][0] ) . '</div>' );
					} else {
						$message = 'error-adding-editing-ad-code';
					}
					break;
				}
				$new_conditionals    = array();
				$unsafe_conditionals = $_REQUEST['acm-conditionals'] ?? array();
				foreach ( $unsafe_conditionals as $index => $unsafe_conditional ) {
					$index       = (int) $index;
					$arguments   = ( isset( $_REQUEST['acm-arguments'][ $index ] ) ) ? sanitize_text_field( $_REQUEST['acm-arguments'][ $index ] ) : '';
					$conditional = array(
						'function'  => sanitize_key( $unsafe_conditional ),
						'arguments' => $arguments,
					);
					if ( ! empty( $conditional['function'] ) ) {
						$new_conditionals[] = $conditional;
					}
				}
				if ( 'add' === $method ) {
					foreach ( $new_conditionals as $new_conditional ) {
						$this->create_conditional( $id, $new_conditional );
					}
					$message = 'ad-code-added';
				} else {
					$this->edit_conditionals( $id, $new_conditionals );
					$message = 'ad-code-updated';
				}
				$this->flush_cache();
				break;
			case 'delete':
				$id = absint( $_REQUEST['id'] );
				$this->delete_ad_code( $id );
				$this->flush_cache();
				$message = 'ad-code-deleted';
				break;
			case 'update_options':
				$options = $this->get_options();
				foreach ( $options as $key => $value ) {
					if ( isset( $_REQUEST[ $key ] ) ) {
						$options[ $key ] = sanitize_text_field( $_REQUEST[ $key ] );
					}
				}
				$this->update_options( $options );
				$message = 'options-saved';
				break;
		}

		if ( isset( $_REQUEST['doing_ajax'] ) && sanitize_text_field( $_REQUEST['doing_ajax'] ) ) {
			if ( 'edit' === $method ) {
				set_current_screen( 'ad-code-manager' );
				$table_class = $this->providers->{$this->current_provider_slug}['table'];
				$this->wp_list_table = new $table_class();
				$this->wp_list_table->prepare_items();
				$new_ad_code = $this->get_ad_code( $id );
				echo $this->wp_list_table->single_row( $new_ad_code );
			}
		} else {
			// @todo support ajax and non-ajax requests
			$redirect_url = add_query_arg( 'message', $message, remove_query_arg( 'message', wp_get_referer() ) );
			wp_safe_redirect( $redirect_url );
			exit();
		}
		exit;
	}

	/**
	 * Get the ad codes stored in our custom post type
	 */
	function get_ad_codes( $query_args = array() ) {
		$allowed_query_params = apply_filters( 'acm_allowed_get_posts_args', array( 'offset' ) );


		/**
		 * Configuration filter: acm_ad_code_count
		 *
		 * By default we limit query to 50 ad codes
		 * Use this filter to change limit
		 */
		$args = array(
			'post_type'   => $this->post_type,
			'numberposts' => apply_filters( 'acm_ad_code_count', 50 ),
		);

		foreach ( (array) $query_args as $query_key => $query_value ) {
			if ( ! in_array( $query_key, $allowed_query_params, true ) ) {
				unset( $query_args[ $query_key ] );
			} else {
				$args[ $query_key ] = $query_value;
			}
		}

		$ad_codes_formatted = wp_cache_get( 'ad_codes', 'acm' );
		if ( false === $ad_codes_formatted ) {
			// Store an empty array when no ad codes exist so this block doesn't run on each page load
			$ad_codes_formatted = array();
			$ad_codes           = get_posts( $args );
			foreach ( $ad_codes as $ad_code_cpt ) {
				$provider_url_vars = array();

				foreach ( $this->current_provider->ad_code_args as  $arg ) {
					$provider_url_vars[ $arg['key'] ] = get_post_meta( $ad_code_cpt->ID, $arg['key'], true );
				}

				$priority = get_post_meta( $ad_code_cpt->ID, 'priority', true );
				$priority = ( ! empty( $priority ) ) ? (int) $priority : 10;

				$operator = get_post_meta( $ad_code_cpt->ID, 'operator', true );
				$operator = ( ! empty( $operator ) ) ? esc_html( $operator ) : $this->logical_operator;

				$ad_codes_formatted[] = array(
					'conditionals' => $this->get_conditionals( $ad_code_cpt->ID ),
					'url_vars'     => $provider_url_vars,
					'priority'     => $priority,
					'operator'     => $operator,
					'post_id'      => $ad_code_cpt->ID,
				);
			}
			wp_cache_add( 'ad_codes', $ad_codes_formatted, 'acm', 3600 );
		}
		return $ad_codes_formatted;
	}

	/**
	 * Get a single ad code
	 *
	 * @param int $post_id Post ID for the ad code that we want
	 * @return array|bool $ad_code Ad code representation of the data
	 */
	function get_ad_code( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$provider_url_vars = array();
		foreach ( $this->current_provider->ad_code_args as $arg ) {
			$provider_url_vars[ $arg['key'] ] = get_post_meta( $post->ID, $arg['key'], true );
		}

		$priority = get_post_meta( $post_id, 'priority', true );
		$priority = ( ! empty( $priority ) ) ? (int) $priority : 10;

		$operator = get_post_meta( $post_id, 'operator', true );
		$operator = ( ! empty( $operator ) ) ? esc_html( $operator ) : $this->logical_operator;

		return array(
			'conditionals' => $this->get_conditionals( $post->ID ),
			'url_vars'     => $provider_url_vars,
			'priority'     => $priority,
			'operator'     => $operator,
			'post_id'      => $post->ID,
		);
	}

	/**
	 * Flush cache
	 */
	function flush_cache() {
		wp_cache_delete( 'ad_codes', 'acm' );
	}

	/**
	 * Get the conditional values for an ad code
	 */
	function get_conditionals( $ad_code_id ) {
		$conditionals = get_post_meta( $ad_code_id, 'conditionals', true );
		if ( empty( $conditionals ) ) {
			$conditionals = array();
		}

		return $conditionals;
	}


	/**
	 * Create a new ad code in the database
	 *
	 * @uses register_ad_code()
	 *
	 * @param array $ad_code
	 * @return int|false|WP_Error post_id or false
	 */
	function create_ad_code( $ad_code = array() ) {
		$titles = array();
		foreach ( $this->current_provider->ad_code_args as $arg ) {
			// We shouldn't create an ad code if any of required fields are not set.
			if ( ! isset( $ad_code[ $arg['key'] ] ) && $arg['required'] === true ) {
				return new WP_Error();
			}
			$titles[] = $ad_code[ $arg['key'] ];
		}
		$acm_post = array(
			'post_title'     => implode( '-', $titles ),
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_type'      => $this->post_type,
		);

		if ( ! is_wp_error( $acm_inserted_post_id = wp_insert_post( $acm_post, true ) ) ) {
			foreach ( $this->current_provider->ad_code_args as $arg ) {
				update_post_meta( $acm_inserted_post_id, $arg['key'], $ad_code[ $arg['key'] ] );
			}
			update_post_meta( $acm_inserted_post_id, 'priority', $ad_code['priority'] );
			update_post_meta( $acm_inserted_post_id, 'operator', $ad_code['operator'] );
			$this->flush_cache();
			return $acm_inserted_post_id;
		}
		return false;
	}

	/**
	 * Update an existing ad code
	 */
	function edit_ad_code( $ad_code_id, $ad_code = array() ) {
		foreach ( $this->current_provider->ad_code_args as $arg ) {
			// If a required argument is not set, we return an error message with the missing parameter
			if ( ! isset( $ad_code[ $arg['key'] ] ) && $arg['required'] === true ) {
				return new WP_Error( 'edit-error', 'Error updating ad code, a parameter for ' . esc_html( $arg['key'] ) . ' is required.' );
			}
		}
		if ( 0 !== $ad_code_id ) {
			foreach ( $this->current_provider->ad_code_args as $arg ) {
				update_post_meta( $ad_code_id, $arg['key'], $ad_code[ $arg['key'] ] );
			}
			update_post_meta( $ad_code_id, 'priority', $ad_code['priority'] );
			update_post_meta( $ad_code_id, 'operator', $ad_code['operator'] );
		}
		$this->flush_cache();
		return $ad_code_id;
	}

	/**
	 * Delete an existing ad code
	 */
	function delete_ad_code( $ad_code_id ) {
		if ( 0 !== $ad_code_id && $this->post_type === get_post_type( $ad_code_id ) ) {
			wp_delete_post( $ad_code_id, true ); // Force delete post.
			$this->flush_cache();
			return true;
		}
	}

	/**
	 * Create conditional.
	 *
	 * @param int   $ad_code_id  ID of our CPT post.
	 * @param array $conditional Conditional to add.
	 * @return bool
	 */
	function create_conditional( $ad_code_id, $conditional ) {
		if ( 0 !== $ad_code_id && ! empty( $conditional ) ) {
			$existing_conditionals = get_post_meta( $ad_code_id, 'conditionals', true );
			if ( ! is_array( $existing_conditionals ) ) {
				$existing_conditionals = array();
			}
			$existing_conditionals[] = array(
				'function'  => $conditional['function'],
				'arguments' => explode( ';', $conditional['arguments'] ),
			);
			return update_post_meta( $ad_code_id, 'conditionals', $existing_conditionals );
		}
		return false;
	}

	/**
	 * Update all conditionals for ad code.
	 *
	 * @since 0.2.0
	 *
	 * @param int   $ad_code_id ID of our CPT post.
	 * @param array $conditionals Conditionals to edit.
	 * @return bool
	 */
	function edit_conditionals( $ad_code_id, $conditionals = array() ) {
		if ( 0 !== $ad_code_id && ! empty( $conditionals ) ) {
			$new_conditionals = array();
			foreach ( $conditionals as $conditional ) {
				if ( '' == $conditional['function'] ) {
					continue;
				}
				$new_conditionals[] = array(
					'function'  => $conditional['function'],
					'arguments' => (array) $conditional['arguments'],
				);
			}
			return update_post_meta( $ad_code_id, 'conditionals', $new_conditionals );
		} elseif ( 0 !== $ad_code_id ) {
			return update_post_meta( $ad_code_id, 'conditionals', array() );
		}
	}

	/**
	 * Hook in our submenu page to the navigation
	 */
	public function action_admin_menu() {
		$hook = add_options_page(
				__( 'Ad Code Manager', 'ad-code-manager' ),
				__( 'Ad Code Manager', 'ad-code-manager' ),
				$this->manage_ads_cap,
				$this->plugin_slug,
				array( $this, 'admin_view_controller' )
		);
		add_action( 'load-' . $hook, array( $this, 'action_load_ad_code_manager' ) );
	}

	/**
	 * Instantiate the List Table and handle our bulk actions on the load of the page.
	 *
	 * @since 0.2.2
	 */
	function action_load_ad_code_manager() {

		// Instantiate this list table
		$table_class = $this->providers->{$this->current_provider_slug}['table'];
		$this->wp_list_table = new $table_class();
		// Handle any bulk action requests
		if ( 'delete' === $this->wp_list_table->current_action() ) {
			check_admin_referer( 'acm-bulk-action', 'bulk-action-nonce' );
			$ad_code_ids = array_map( 'intval', $_REQUEST['ad-codes'] ?? array() );
			foreach ( $ad_code_ids as $ad_code_id ) {
				$this->delete_ad_code( $ad_code_id );
			}
			$redirect_url = add_query_arg( 'message', 'ad-codes-deleted', remove_query_arg( 'message', wp_get_referer() ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Print the admin interface for managing the ad codes.
	 */
	function admin_view_controller() {
		require_once dirname( AD_CODE_MANAGER_FILE ) . '/views/ad-code-manager.tpl.php';
	}

	/**
	 * Register a custom widget to display ad zones
	 */
	function register_widget() {
		register_widget( 'ACM_Ad_Zones' );
	}

	/**
	 * Register scripts and styles
	 */
	function register_scripts_and_styles() {
		global $pagenow;

		// Only load this on the proper page
		if ( 'options-general.php' !== $pagenow || ! isset( $_GET['page'] ) || $_GET['page'] != $this->plugin_slug ) {
			return;
		}

		wp_enqueue_style( 'acm-style', plugins_url( '/', AD_CODE_MANAGER_FILE ) . '/acm.css', array(), AD_CODE_MANAGER_VERSION );
		wp_enqueue_script( 'acm', plugins_url( '/', AD_CODE_MANAGER_FILE ) . '/acm.js', array( 'jquery' ), AD_CODE_MANAGER_VERSION, true );
	}

	/**
	 * Register an ad tag with the plugin so it can be used
	 * on the frontend of the site
	 *
	 * @since 0.1
	 *
	 * @param string $tag          Ad tag for this instance of code
	 * @param string $url          Script URL for ad code
	 * @param array  $conditionals WordPress-style conditionals for where this code should be displayed
	 * @param int    $priority     What priority this registration runs at
	 * @param array  $url_vars     Replace tokens in $script with these values
	 * @param int    $priority     Priority of the ad code in comparison to others
	 * @return bool|WP_Error $success Whether we were successful in registering the ad tag
	 */
	function register_ad_code( $tag, $url, $conditionals = array(), $url_vars = array(), $priority = 10, $operator = false ) {

		// Run $url aganist a whitelist to make sure it's a safe URL
		if ( ! $this->validate_script_url( $url ) ) {
			return;
		}

		// @todo Sanitize the conditionals against our possible set of conditionals so that users
		// can't just run arbitrary functions. These are whitelisted on execution of the ad code so we're fine for now

		// @todo Sanitize all of the other input

		// Make sure our priority is an integer
		if ( ! is_int( $priority ) ) {
			$priority = 10;
		}

		// Make sure our operator is 'OR' or 'AND'
		if ( ! $operator || ! in_array( $operator, array( 'AND', 'OR' ) ) ) {
			$operator = $this->logical_operator;
		}

		// Save the ad code to our set of ad codes
		$this->ad_codes[ $tag ][] = array(
			'url'          => $url,
			'priority'     => $priority,
			'operator'     => $operator,
			'conditionals' => $conditionals,
			'url_vars'     => $url_vars,
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
		if ( empty( $ad_codes ) ) {
			return;
		}

		foreach ( (array) $ad_codes as $key => $ad_code ) {
			$default = array(
				'tag'          => '',
				'url'          => '',
				'conditionals' => array(),
				'url_vars'     => array(),
				'priority'     => 10,
				'operator'     => $this->logical_operator,
			);
			$ad_code = array_merge( $default, $ad_code );

			foreach ( (array) $this->ad_tag_ids as $default_tag ) {

				/**
				 * 'enable_ui_mapping' is a special argument which means this ad tag can be
				 * mapped with ad codes through the admin interface. If that's the case, we
				 * want to make sure those ad codes are only registered with the tag.
				 */
				if ( isset( $default_tag['enable_ui_mapping'] ) && $default_tag['tag'] != $ad_code['url_vars']['tag'] ) {
					continue;
				}

				/**
				 * Configuration filter: acm_default_url
				 * If you don't specify a URL for your ad code when registering it in
				 * the WordPress admin or at a code level, you can simply apply it with
				 * a custom filter defined.
				 */
				$ad_code['priority'] = $ad_code['priority'] === '' ? 10 : (int) $ad_code['priority']; // make sure priority is int, if it's unset, we set it to 10

				// Make sure our operator is 'OR' or 'AND'
				if ( ! $ad_code['operator'] || ! in_array( $ad_code['operator'], array( 'AND', 'OR' ) ) ) {
					$operator = $this->logical_operator;
				}

				$this->register_ad_code( $default_tag['tag'], apply_filters( 'acm_default_url', $ad_code['url'] ), $ad_code['conditionals'], array_merge( $default_tag['url_vars'], $ad_code['url_vars'] ), $ad_code['priority'], $ad_code['operator'] );
			}
		}
	}

	/**
	 * Return the ad code for an ad ID.
	 *
	 * @since 0.6.0
	 *
	 * @param string $tag_id Unique ID for the ad tag
	 * @return string The ad code. May be an empty string.
	 */
	function get_acm_tag( $tag_id ): string {
		/**
		 * See http://adcodemanager.wordpress.com/2013/04/10/hi-all-on-a-dotcom-site-that-uses/
		 *
		 * Configuration filter: acm_disable_ad_rendering
		 * Should be boolean, defaulting to disabling ads on previews
		 */
		if ( apply_filters( 'acm_disable_ad_rendering', is_preview() ) ) {
			return '';
		}

		$code_to_display = $this->get_matching_ad_code( $tag_id );

		// get_matching_ad_code can return an empty value.
		if ( empty( $code_to_display ) ) {
			return '';
		}

		// Run $url against a safelist to make sure it's a safe URL.
		if ( ! $this->validate_script_url( $code_to_display['url'] ) ) {
			return '';
		}

		/**
		 * Configuration filter: acm_output_html
		 * Support multiple ad formats ( e.g. Javascript ad tags, or simple HTML tags )
		 * by adjusting the HTML rendered for a given ad tag.
		 */
		$output_html = apply_filters( 'acm_output_html', $this->current_provider->output_html, $tag_id );

		// Parse the output and replace any tokens we have left. But first, load the script URL.
		$output_html = str_replace( '%url%', $code_to_display['url'], $output_html );

		/**
		 * Configuration filter: acm_output_tokens
		 * Register output tokens depending on the needs of your setup. Tokens are the
		 * keys to be replaced in your script URL.
		 */
		$output_tokens = apply_filters( 'acm_output_tokens', $this->current_provider->output_tokens, $tag_id, $code_to_display );
		foreach ( (array) $output_tokens as $token => $val ) {
			$output_html = str_replace( $token, esc_attr( $val ), $output_html );
		}

		/**
		 * Configuration filter: acm_output_html_after_tokens_processed
		 * In some rare cases you might want to filter html after the tokens are processed
		 */
		$output_html = apply_filters( 'acm_output_html_after_tokens_processed', $output_html, $tag_id );

		return $output_html;
	}

	/**
	 * Display the ad code for an ad ID.
	 *
	 * Example:
	 *   do_action( 'acm_tag, 'your_tag_id' )
	 *
	 * @param string $tag_id Unique ID for the ad tag.
	 */
	public function action_acm_tag( $tag_id ): void {
		echo $this->get_acm_tag( $tag_id );
	}

	/**
	 * Of all the ad codes registered, get the one that matches our current context
	 *
	 * @since 0.4
	 */
	public function get_matching_ad_code( $tag_id ) {
		global $wp_query;

		static $checked_ad_codes = array();

		// If there aren't any ad codes, it's not worth it for us to do anything.
		if ( ! isset( $this->ad_codes[ $tag_id ] ) ) {
			return;
		}

		// This method might be expensive when there's a lot of ad codes
		// So instead of executing over and over again, return cached matching ad code.
		$cache_key = "acm:{$tag_id}:" . md5( serialize( $wp_query->query_vars ) );

		/**
		 * Filters the amount of time to cache the matching ad code.
		 *
		 * Returning false to this filter will cause the ad code to be cached
		 * only for the duration of the request, not within the object cache.
		 *
		 * @param int    $cache_expiration The amount of time, in seconds, to cache the matching ad code. Default 10 minutes.
		 * @param string $tag_id           The tag ID.
		 */
		$cache_expiration = apply_filters( 'acm_matching_ad_code_cache_expiration', 600, $tag_id );

		if ( false === $cache_expiration ) {
			$ad_code = isset( $checked_ad_codes[ $cache_key ] ) ? $checked_ad_codes[ $cache_key ] : false;
		} else {
			$ad_code = wp_cache_get( $cache_key, 'acm' );
		}

		if ( false !== $ad_code ) {
			return $ad_code;
		}

		/**
		 * Prevent $post polution if ad code is getting rendered inside a loop:
		 *
		 * Most of conditionals are getting checked against global $post,
		 * Getting matched ad code inside the loop might result in wrong ad code matched.
		 *
		 * Filter is for back compat since not thoroughly tested
		 */
		if ( apply_filters( 'acm_reset_postdata_before_match', false ) ) {
			wp_reset_postdata();
		}

		// Run our ad codes through all of the conditionals to make sure we should
		// be displaying it
		$display_codes = array();
		foreach ( (array) $this->ad_codes[ $tag_id ] as $ad_code ) {

			// If the ad code doesn't have any conditionals
			// we should add it to the display list
			if ( empty( $ad_code['conditionals'] ) && apply_filters( 'acm_display_ad_codes_without_conditionals', false ) ) {
				$display_codes[] = $ad_code;
				continue;
			}

			// If the ad code doesn't have any conditionals
			// and configuration filter acm_display_ad_codes_without_conditionals returns false
			// We should should skip it

			if ( empty( $ad_code['conditionals'] ) && ! apply_filters( 'acm_display_ad_codes_without_conditionals', false ) ) {
				continue;
			}

			$include = true;
			foreach ( $ad_code['conditionals'] as $conditional ) {
				// If the conditional was passed as an array, then we have a complex rule.
				// Otherwise, we have a function name and expect rule.
				if ( is_array( $conditional ) ) {
					$cond_func = $conditional['function'];
					if ( ! empty( $conditional['arguments'] ) ) {
						$cond_args = $conditional['arguments'];
					} else {
						$cond_args = array();
					}
					$cond_result = $conditional['result'] ?? true;
				} else {
					$cond_func   = $conditional;
					$cond_args   = array();
					$cond_result = true;
				}

				// Special trick: include 'not_' in front of the function name to reverse the result.
				if ( 0 === strpos( $cond_func, 'not_' ) ) {
					$cond_func   = ltrim( $cond_func, 'not_' );
					$cond_result = false;
				}

				// Don't run the conditional if the conditional function doesn't exist or isn't in our safelist.
				if ( ! is_callable( $cond_func ) || ! in_array( $cond_func, $this->whitelisted_conditionals ) ) {
					continue;
				}

				// Run our conditional and use any arguments that were passed
				if ( ! empty( $cond_args ) ) {
					/**
					 * Configuration filter: acm_conditional_args
					 * For certain conditionals (has_tag, has_category), you might need to
					 * pass additional arguments.
					 */
					$result = call_user_func_array( $cond_func, apply_filters( 'acm_conditional_args', $cond_args, $cond_func ) );
				} else {
					$result = $cond_func();
				}

				// If our results don't match what we need, don't include this ad code
				if ( $cond_result !== $result ) {
					$include = false;
				} else {
					$include = true;
				}

				// If we have matching conditional and $ad_code['operator'] equals OR just break from the loop and do not try to evaluate others
				if ( $include && 'OR' === $ad_code['operator'] ) {
					break;
				}

				// If $ad_code['operator'] equals AND and one conditional evaluates false, skip this ad code
				if ( ! $include && 'AND' === $ad_code['operator'] ) {
					break;
				}
			}

			// If we're supposed to include the ad code even after we've run the conditionals,
			// let's do it
			if ( $include ) {
				$display_codes[] = $ad_code;
			}
		}

		// Don't do anything if we've ended up with no ad codes
		if ( empty( $display_codes ) ) {
			return;
		}

		// Prioritize the display of the ad codes based on
		// the priority argument for the ad code
		$prioritized_display_codes = array();
		foreach ( $display_codes as $display_code ) {
			$priority                                 = $display_code['priority'];
			$prioritized_display_codes[ $priority ][] = $display_code;
		}
		ksort( $prioritized_display_codes, SORT_NUMERIC );

		$shifted_prioritized_display_codes = array_shift( $prioritized_display_codes );

		$code_to_display = array_shift( $shifted_prioritized_display_codes );

		if ( false === $cache_expiration ) {
			$checked_ad_codes[ $cache_key ] = $code_to_display;
		} else {
			wp_cache_add( $cache_key, $code_to_display, 'acm', $cache_expiration );
		}

		return $code_to_display;
	}

	/**
	 * Filter the output tokens used in $this->action_acm_tag to include our URL vars
	 *
	 * @since 0.1
	 *
	 * @return array $output Placeholder tokens to be replaced with their values
	 */
	function filter_output_tokens( $output_tokens, $tag_id, $code_to_display ) {
		if ( ! isset( $code_to_display['url_vars'] ) || ! is_array( $code_to_display['url_vars'] ) ) {
			return $output_tokens;
		}

		foreach ( $code_to_display['url_vars'] as $url_var => $val ) {
			$new_key                   = '%' . $url_var . '%';
			$output_tokens[ $new_key ] = $val;
		}

		return $output_tokens;
	}

	/**
	 * Ensure the URL being used passes our whitelist check
	 *
	 * @since 0.1
	 * @see https://gist.github.com/1623788
	 */
	function validate_script_url( $url ) {
		// If url is empty, there's nothing to validate
		// Fixes issue with DFP JS
		if ( empty( $url ) ) {
			return true;
		}

		$domain = wp_parse_url( $url, PHP_URL_HOST );

		// Check if we match the domain exactly
		if ( in_array( $domain, $this->current_provider->whitelisted_script_urls, true ) ) {
			return true;
		}

		$valid = false;

		foreach ( $this->current_provider->whitelisted_script_urls as $whitelisted_domain ) {
			$whitelisted_domain = '.' . $whitelisted_domain; // Prevent things like 'evilsitetime.com'
			if ( strpos( $domain, $whitelisted_domain ) === ( strlen( $domain ) - strlen( $whitelisted_domain ) ) ) {
				$valid = true;
				break;
			}
		}
		return $valid;
	}

	/**
	 * Shortcode function
	 *
	 * @since 0.2
	 *
	 * @return string HTML output. May be an empty string if no ad code is found.
	 */
	function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);

		$id = sanitize_text_field( $atts['id'] );
		if ( empty( $id ) ) {
			return '';
		}

		return $this->get_acm_tag( $id );
	}

}
