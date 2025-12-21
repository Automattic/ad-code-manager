<?php
/**
 * Conditional Autocomplete functionality for Ad Code Manager.
 *
 * Provides autocomplete/dropdown for conditional arguments like categories, tags, etc.
 *
 * @package Automattic\AdCodeManager\UI
 * @since 0.9.0
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\UI;

/**
 * Handles autocomplete functionality for conditional arguments.
 *
 * @since 0.9.0
 */
final class Conditional_Autocomplete {

	/**
	 * Minimum characters before autocomplete starts searching.
	 *
	 * @var int
	 */
	private const MIN_CHARS = 3;

	/**
	 * Maximum number of results to return.
	 *
	 * @var int
	 */
	private const MAX_RESULTS = 100;

	/**
	 * Register action and filter hook callbacks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_acm_search_terms', array( $this, 'ajax_search_terms' ) );
	}

	/**
	 * Enqueue scripts and styles for the autocomplete functionality.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( 'settings_page_ad-code-manager' !== $hook_suffix ) {
			return;
		}

		// Enqueue Select2 from WordPress (available since WP 4.0).
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );

		// Enqueue our autocomplete handler.
		wp_enqueue_script(
			'acm-conditional-autocomplete',
			plugins_url( 'acm-autocomplete.js', dirname( __DIR__, 2 ) . '/ad-code-manager.php' ),
			array( 'jquery', 'selectWoo' ),
			filemtime( dirname( __DIR__, 2 ) . '/acm-autocomplete.js' ),
			true
		);

		// Pass configuration to JavaScript.
		wp_localize_script(
			'acm-conditional-autocomplete',
			'acmAutocomplete',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'acm-search-terms' ),
				'minChars'     => self::MIN_CHARS,
				'conditionals' => $this->get_autocomplete_conditionals(),
				'i18n'         => array(
					'searching'     => __( 'Searching...', 'ad-code-manager' ),
					'noResults'     => __( 'No results found', 'ad-code-manager' ),
					'inputTooShort' => sprintf(
						/* translators: %d: minimum number of characters */
						__( 'Please enter %d or more characters', 'ad-code-manager' ),
						self::MIN_CHARS
					),
					'errorLoading'  => __( 'Error loading results', 'ad-code-manager' ),
				),
			)
		);
	}

	/**
	 * Get the conditionals that support autocomplete.
	 *
	 * Returns a mapping of conditional function names to their search configuration.
	 *
	 * @return array<string, array{type: string, taxonomy?: string, post_type?: string}>
	 */
	private function get_autocomplete_conditionals(): array {
		$conditionals = array(
			'is_category'  => array(
				'type'     => 'taxonomy',
				'taxonomy' => 'category',
			),
			'has_category' => array(
				'type'     => 'taxonomy',
				'taxonomy' => 'category',
			),
			'is_tag'       => array(
				'type'     => 'taxonomy',
				'taxonomy' => 'post_tag',
			),
			'has_tag'      => array(
				'type'     => 'taxonomy',
				'taxonomy' => 'post_tag',
			),
			'is_page'      => array(
				'type'      => 'post_type',
				'post_type' => 'page',
			),
			'is_single'    => array(
				'type'      => 'post_type',
				'post_type' => 'post',
			),
		);

		/**
		 * Filters the conditionals that support autocomplete.
		 *
		 * Allows themes and plugins to add or modify which conditionals
		 * get autocomplete functionality and how they search.
		 *
		 * @since 0.9.0
		 *
		 * @param array $conditionals Associative array of conditional configurations.
		 *                            Each key is a conditional function name.
		 *                            Each value is an array with:
		 *                            - 'type': 'taxonomy' or 'post_type'
		 *                            - 'taxonomy': (for taxonomy type) The taxonomy to search
		 *                            - 'post_type': (for post_type type) The post type to search
		 */
		return apply_filters( 'acm_autocomplete_conditionals', $conditionals );
	}

	/**
	 * AJAX handler for searching terms.
	 *
	 * @return void
	 */
	public function ajax_search_terms(): void {
		check_ajax_referer( 'acm-search-terms', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ad-code-manager' ) ) );
		}

		$search      = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$conditional = isset( $_GET['conditional'] ) ? sanitize_key( $_GET['conditional'] ) : '';
		$type        = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$taxonomy    = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
		$post_type   = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';

		if ( strlen( $search ) < self::MIN_CHARS ) {
			wp_send_json_error( array( 'message' => __( 'Search term too short.', 'ad-code-manager' ) ) );
		}

		$results = array();

		if ( 'taxonomy' === $type && ! empty( $taxonomy ) ) {
			$results = $this->search_taxonomy_terms( $search, $taxonomy );
		} elseif ( 'post_type' === $type && ! empty( $post_type ) ) {
			$results = $this->search_posts( $search, $post_type );
		}

		/**
		 * Filters the autocomplete search results.
		 *
		 * @since 0.9.0
		 *
		 * @param array  $results     The search results.
		 * @param string $search      The search term.
		 * @param string $conditional The conditional function name.
		 * @param string $type        The search type ('taxonomy' or 'post_type').
		 */
		$results = apply_filters( 'acm_autocomplete_results', $results, $search, $conditional, $type );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Search for taxonomy terms.
	 *
	 * @param string $search   The search term.
	 * @param string $taxonomy The taxonomy to search.
	 * @return array<int, array{id: string, text: string}>
	 */
	private function search_taxonomy_terms( string $search, string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'search'     => $search,
				'number'     => self::MAX_RESULTS,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$results = array();
		foreach ( $terms as $term ) {
			$results[] = array(
				'id'   => $term->slug,
				'text' => sprintf( '%s (%s)', $term->name, $term->slug ),
			);
		}

		return $results;
	}

	/**
	 * Search for posts.
	 *
	 * @param string $search    The search term.
	 * @param string $post_type The post type to search.
	 * @return array<int, array{id: string, text: string}>
	 */
	private function search_posts( string $search, string $post_type ): array {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				's'              => $search,
				'posts_per_page' => self::MAX_RESULTS,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $posts ) ) {
			return array();
		}

		$results = array();
		foreach ( $posts as $post ) {
			// For is_page/is_single, we can use ID, slug, or title.
			$results[] = array(
				'id'   => (string) $post->ID,
				'text' => sprintf( '%s (ID: %d)', $post->post_title, $post->ID ),
			);
		}

		return $results;
	}
}
