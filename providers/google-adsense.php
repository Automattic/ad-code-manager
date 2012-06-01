<?php
/**
 * Google AdSense Ad Provider for Ad Code manager
 */
class Google_AdSense_ACM_Provider extends ACM_Provider {
	/**
	 * Register default options for Google AdSense
	 *
	 * @uses apply_filters, parent::__construct
	 * @return null
	 */
	public function __construct() {
		// Default output HTML
		$this->output_html = '<script type="text/javascript">GA_googleFillSlot( "%slot%" );</script>';

		// Default Ad Tag Ids (you will pass this in your shortcode or template tag)
		$this->ad_tag_ids = array(
			array(
				'tag' => 'popunder',
				'url_vars' => array(
					'slot' => 'generic'
				)
			),
			array(
				'tag' => 'background',
				'url_vars' => array(
					'slot' => 'generic'
				)
			),
			array(
				'tag' => 'masthead',
				'url_vars' => array(
					'slot' => 'generic'
				)
			)
		);

		//Since URLs aren't used with AdSense, value 'null' is included as ACM uses parse_url to validate URLs
		$this->whitelisted_script_urls = array( '%slot%', null );

		//Specify table columns
		$this->columns = apply_filters( 'acm_provider_columns', array( 'slot' => 'Slot' ) );

		parent::__construct();
	}
}

/**
 * Google AdSense list table for Ad Code Manager
 */
class Google_AdSense_ACM_WP_List_Table extends ACM_WP_List_Table {
	/**
	 * Register table settings
	 *
	 * @uses parent::__construct
	 * @return null
	 */
	public function __construct() {
		parent::__construct( array(
			'singular'=> 'google_adsense_acm_wp_list_table',
			'plural' => 'google_adsense_acm_wp_list_table',
			'ajax'	=> true
		) );
	 }

	/**
	 * Specify table columns
	 *
	 * @uses apply_filters
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'id'             => __( 'ID', 'ad-code-manager' ),
			'slot'      => __( 'Slot', 'ad-code-manager' ),
			'priority'       => __( 'Priority', 'ad-code-manager' ),
			'conditionals'   => __( 'Conditionals', 'ad-code-manager' ),
		);

		return apply_filters( 'acm_list_table_columns', $columns );
	}

	/**
	 * Output ad slot in table
	 *
	 * @param array $item
	 * @uses esc_html, this::row_actions_output
	 * @return string
	 */
	public function column_slot( $item ) {
		$output = esc_html( $item[ 'url_vars' ][ 'slot' ] );
		$output .= $this->row_actions_output( $item );

		return $output;
	}
}
?>