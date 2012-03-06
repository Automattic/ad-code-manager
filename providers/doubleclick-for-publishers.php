<?php
/**
 * Doubleclick for Publishers Ad Provider for Ad Code manager
 *
 * @since 0.1.3
 */
class Doubleclick_For_Publishers_ACM_Provider extends ACM_Provider {
	function __construct() {
		$this->output_html = '<script type="text/javascript" src="%url%"></script>';
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
		$this->whitelisted_script_urls = array( 'ad.doubleclick.net' );
		$this->columns = array( 'site_name' => 'Site Name', 'zone1' => 'zone1' );
		parent::__construct();
	}
}
