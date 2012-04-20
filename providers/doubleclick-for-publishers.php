<?php
/**
 * Doubleclick for Publishers Ad Provider for Ad Code manager
 *
 * @since 0.1.3
 */
class Doubleclick_For_Publishers_ACM_Provider extends ACM_Provider {
	function __construct() {
		// Default output HTML
		$this->output_html = '<script type="text/javascript" src="%url%"></script>';
		
		// Default Ad Tag Ids (you will pass this in your shortcode or template tag)
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
						'fold' => 'btf'
				)
			),
			array(
					'tag' => '160x600-atf',
					'url_vars' => array(
						'sz' => '160x600',
						'fold' => 'atf'
				)
			),
			array(
					'tag' => '1x1',
					'url_vars' => array(
						'sz' => '1x1',
						'fold' => 'int',
						'pos' => 'top',
						'width' => '1',
						'height' => '1',						
					)
			),
		);
		
		// Only allow ad tags called from following URLS
		$this->whitelisted_script_urls = array( 'ad.doubleclick.net' );
		$this->columns = array( 'site_name' => 'Site Name', 'zone1' => 'zone1' );
		parent::__construct();
	}
}

class Doubleclick_For_Publishers_ACM_WP_List_Table extends ACM_WP_List_Table {
	function __construct() {
		parent::__construct( array(
			'singular'=> 'doubleclick_for_publishers_acm_wp_list_table', //Singular label
			'plural' => 'doubleclick_for_publishers_acm_wp_list_table', //plural label, also this well be one of the table css class
			'ajax'	=> true 
		) );
	 }
	
	
	/**
	 * This is nuts and bolts of table representation
	 *
	 */
	function get_columns() {
		return $columns = array(
			'col_acm_post_id'=>__( 'ID' ),
			'col_acm_site_name'=>__( 'Site Name' ),
			'col_acm_zone1'=>__( 'Zone1' ),
			'col_acm_priority'=>__( 'Priority' ),
		);
	} 
}
