<?php
/**
 * Base class for ad providers.
 *
 * @package Automattic\AdCodeManager
 */

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
	/**
	 * Domains that ad code script URLs may be loaded from.
	 *
	 * @var string[]
	 */
	public $whitelisted_script_urls = array();

	/**
	 * HTML template for an ad tag, with tokens such as %url% to be replaced.
	 *
	 * @var string
	 */
	public $output_html;

	/**
	 * Tokens to replace in the output HTML, keyed by token, e.g. '%width%'.
	 *
	 * @var array
	 */
	public $output_tokens = array();

	/**
	 * Default ad tags, each with a tag ID and its URL vars.
	 *
	 * @var array
	 */
	public $ad_tag_ids;

	/**
	 * Fields that make up an ad code, shown in the admin.
	 *
	 * @var array
	 */
	public $ad_code_args = array();

	/**
	 * User agent of the ad network's crawler. If set, a robots.txt rule is added for it.
	 *
	 * @var string
	 */
	public $crawler_user_agent;
	
	/**
	 * Sets the default ad code fields and output HTML if the child class hasn't, and
	 * hooks the robots.txt rule for the provider's crawler.
	 */
	public function __construct() {
		if ( empty( $this->ad_code_args ) ) {
			// This is not actual data, but the format the data takes.
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
				add_action( 'do_robotstxt', array( $this, 'action_do_robotstxt' ), 10 ); // phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.do_robotstxt -- Adds the provider's crawler rules; nothing for the plugin to flush.
			}
		}
	}

	/**
	 * Adds a robots.txt rule for the provider's crawler.
	 *
	 * The crawler is disallowed from the whole site if the site is set to discourage
	 * search engines, and allowed everywhere otherwise.
	 */
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
