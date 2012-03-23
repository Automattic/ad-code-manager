<?php
/**
 * Skeleton Ad Provider class
 *
 * Each of those properties should be correctly set in a child class
 * 
 * @property array $whitelisted_script_urls Array of whitelisted remote urls
 * @property string $output_html Html of an ad tag
 * @property array $output_tokens Array of tokens that will be replaced in %url%
 * @property array $ad_tag_ids Set of default ad tags (e.g. 2 leaderboards, 300x250, etc)
 * @property array $columns array of properties of an ad code in format "slug" => 'Column title'
 * 
 * @since v0.1.3
 */
class ACM_Provider
{
	public $whitelisted_script_urls = array();
	public $output_html;
	public $output_tokens = array();
	public $ad_tag_ids;
	public $columns = array();
	
	function __construct() {
		if ( empty( $this->columns ) ) {
			// This is not actual data, but rather format:
			// slug => Title
			$this->columns = array('name' => 'Name');
		}
		
		// Could be filtered via acm_output_html filter
		// @see Ad_Code_Manager::action_acm_tag()
		if ( empty( $this->output_html ) ) {
			$this->output_html = '<script type="text/javascript" src="%url%"></script>';
		}
	}
}