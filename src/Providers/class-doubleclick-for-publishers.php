<?php
/**
 * DoubleClick for Publishers Ad Provider for Ad Code manager
 *
 * @package Automattic\AdCodeManager
 * @since 0.1.3
 */

/**
 * Empty class that nothing uses, left over from an earlier version of the provider.
 */
class Doubleclick_For_Publishers_Columns {

}

/**
 * Provider for DoubleClick for Publishers ad tags, loaded from ad.doubleclick.net.
 */
class Doubleclick_For_Publishers_ACM_Provider extends ACM_Provider {
	/**
	 * User agent of Google's ad crawler, which gets a robots.txt rule.
	 *
	 * @var string
	 */
	public $crawler_user_agent = 'Mediapartners-Google';

	/**
	 * Sets the output HTML, default ad tags, ad code fields and whitelisted script domain.
	 */
	public function __construct() {
		// Default output HTML.
		$this->output_html = '<script type="text/javascript" src="%url%"></script>';

		// Default Ad Tag Ids (you will pass this in your shortcode or template tag).
		$this->ad_tag_ids   = array(
			array(
				'tag'      => '728x90-atf',
				'url_vars' => array(
					'sz'   => '728x90',
					'fold' => 'atf',
				),
			),
			array(
				'tag'      => '728x90-btf',
				'url_vars' => array(
					'sz'   => '728x90',
					'fold' => 'btf',
				),
			),
			array(
				'tag'      => '300x250-atf',
				'url_vars' => array(
					'sz'   => '300x250',
					'fold' => 'atf',
				),
			),
			array(
				'tag'      => '300x250-btf',
				'url_vars' => array(
					'sz'   => '300x250',
					'fold' => 'btf',
				),
			),
			array(
				'tag'      => '160x600-atf',
				'url_vars' => array(
					'sz'   => '160x600',
					'fold' => 'atf',
				),
			),
			array(
				'tag'      => '1x1',
				'url_vars' => array(
					'sz'     => '1x1',
					'fold'   => 'int',
					'pos'    => 'top',
					'width'  => '1',
					'height' => '1',
				),
			),
		);
		$this->ad_code_args = array(
			array(
				'key'      => 'site_name',
				'label'    => __( 'Site Name', 'ad-code-manager' ),
				'editable' => true,
				'required' => true,
			),
			array(
				'key'      => 'zone1',
				'label'    => __( 'zone1', 'ad-code-manager' ),
				'editable' => true,
				'required' => true,
			),
		);
		// Only allow ad tags called from following URLS.
		$this->whitelisted_script_urls = array( 'ad.doubleclick.net' );

		parent::__construct();
	}
}

/**
 * List table for DoubleClick for Publishers ad codes.
 */
class Doubleclick_For_Publishers_ACM_WP_List_Table extends ACM_WP_List_Table {
	/**
	 * Sets up the list table with AJAX support.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'doubleclick_for_publishers_acm_wp_list_table', // Singular label.
				'plural'   => 'doubleclick_for_publishers_acm_wp_list_table', // plural label, also this well be one of the table css class.
				'ajax'     => true,
			)
		);
	}

	/**
	 * Replace the list table columns with this provider's columns
	 *
	 * Hooked to the `acm_list_table_columns` filter.
	 *
	 * @return array The columns that shall be used
	 */
	public function filter_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'id'           => __( 'ID', 'ad-code-manager' ),
			'site_name'    => __( 'Site Name', 'ad-code-manager' ),
			'zone1'        => __( 'Zone1', 'ad-code-manager' ),
			'priority'     => __( 'Priority', 'ad-code-manager' ),
			'operator'     => __( 'Logical Operator', 'ad-code-manager' ),
			'conditionals' => __( 'Conditionals', 'ad-code-manager' ),
		);
	}

	/**
	 * This is nuts and bolts of table representation
	 */
	public function get_columns() {
		add_filter( 'acm_list_table_columns', array( $this, 'filter_columns' ) );
		return parent::get_columns();
	}

	/**
	 * Representation of the site name
	 *
	 * @param array $item Ad code, as returned by Ad_Code_Manager::get_ad_codes().
	 * @return string HTML for the column, with the row actions.
	 */
	public function column_site_name( $item ) {
		$output  = esc_html( $item['url_vars']['site_name'] );
		$output .= $this->row_actions_output( $item );
		return $output;
	}

	/**
	 * Representation of zone1
	 *
	 * @param array $item Ad code, as returned by Ad_Code_Manager::get_ad_codes().
	 * @return string HTML for the column.
	 */
	public function column_zone1( $item ) {
		return esc_html( $item['url_vars']['zone1'] );
	}
}
