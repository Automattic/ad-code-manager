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
 * @property array $ad_code_args array of properties of an ad code
 *
 * @since v0.1.3
 */
class ACM_Provider {
	public $whitelisted_script_urls = array();
	public $output_html;
	public $output_tokens = array();
	public $ad_tag_ids;
	public $ad_code_args = array();
	public $crawler_user_agent;
	
	public function __construct() {
		if ( empty( $this->ad_code_args ) ) {
			// This is not actual data, but rather format:
			$this->ad_code_args = array(
				array(
					'key'      => 'name',
					'label'    => __( 'Name', 'ad-code-manager' ),
					'editable' => true,
					'required' => true,
				),
			);
		}
		/**
		 * Filters the ad code arguments/fields for a provider.
		 *
		 * This filter is also applied in the main Ad_Code_Manager class after
		 * provider instantiation. It allows modification of the fields displayed
		 * in the admin UI for this provider's ad codes.
		 *
		 * @since 0.1
		 *
		 * @param array $ad_code_args Array of field configurations.
		 */
		$this->ad_code_args = apply_filters( 'acm_ad_code_args', $this->ad_code_args );

		// Could be filtered via acm_output_html filter.
		// @see Ad_Code_Manager::action_acm_tag().
		if ( empty( $this->output_html ) ) {
			$this->output_html = '<script type="text/javascript" src="%url%"></script>';
		}

		if ( ! empty( $this->crawler_user_agent ) ) {
			/**
			 * Filters whether to add robots.txt rules for ad crawlers.
			 *
			 * When a provider has a crawler_user_agent defined, this filter
			 * controls whether rules are added to robots.txt for that crawler.
			 *
			 * @since 0.1
			 *
			 * @param bool         $should_do Whether to add robots.txt rules. Default true.
			 * @param ACM_Provider $provider  The provider instance.
			 */
			$should_do_robotstxt = apply_filters( 'acm_should_do_robotstxt', true, $this );

			if ( true === $should_do_robotstxt ) {
				add_action( 'do_robotstxt', array( $this, 'action_do_robotstxt' ), 10 );
			}
		}
	}

	public function action_do_robotstxt() {
		$public = get_option( 'blog_public' );

		$disallowed = array();

		if ( '0' == $public ) {
			$disallowed[] = '/';
		} else {
			$disallowed[] = '';
		}

		/**
		 * Filters the disallowed paths for ad crawlers in robots.txt.
		 *
		 * Allows modification of which paths should be disallowed for the
		 * ad network's crawler in the robots.txt file.
		 *
		 * @since 0.1
		 *
		 * @param array        $disallowed Array of paths to disallow. Default array('')
		 *                                 or array('/') if blog is not public.
		 * @param ACM_Provider $provider   The provider instance.
		 */
		$disallowed = apply_filters( 'acm_robotstxt_disallow', $disallowed, $this );

		// If we have no disallows to add, don't add anything (including User-agent).
		if ( ! is_array( $disallowed ) || empty( $disallowed ) ) {
			return;
		}

		echo 'User-agent: ' . esc_html( $this->crawler_user_agent ) . PHP_EOL;

		foreach ( $disallowed as $disallow ) {
			echo 'Disallow: ' . esc_html( $disallow ) . PHP_EOL;
		}

		echo PHP_EOL;
	}
}
