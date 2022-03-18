<?php
/**
 * Ad Code Manager contextual help class
 *
 * @package Automattic\AdCodeManager
 * @since 0.6.0
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager\UI;

use WP_Screen;

/**
 * User Interface changes for the plugins actions.
 *
 * @since 0.6.0
 */
final class Contextual_Help {

	/**
	 * Register action and filter hook callbacks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'current_screen', array( $this, 'render' ) );
	}

	/**
	 * Add contextual help to the Ad Code Manager admin pages.
	 *
	 * @since 0.6.0
	 *
	 * @param WP_Screen $screen Screen object.
	 * @return void
	 */
	public function render( WP_Screen $screen ): void {
		if ( 'settings_page_ad-code-manager' !== $screen->id ) {
			return;
		}

		ob_start();
		?>
		<p><?php esc_html_e( 'Ad Code Manager gives non-developers an interface in the WordPress admin for configuring your complex set of ad codes. Generally an "Ad Code" is a set of parameters you need to pass to an ad server, so it can serve the proper ad.', 'ad-code-manager' ); ?></p>
		<p><?php echo wp_kses_post( __( 'Some code-level configuration may be necessary to display the ads. See the <a href="https://github.com/Automattic/ad-code-manager">GitHub repository</a> for developer information.', 'ad-code-manager' ) ); ?></p>
		<?php
		$overview = ob_get_clean();

		ob_start();
		?>
		<p><?php esc_html_e( 'Choose the ad network you use, and you will see a set of required fields to fill in, such as IDs. You can also set conditionals for each ad tag, which restricts the contexts for when the adverts are displayed. Priorities work pretty much the same way they work in WordPress. Lower numbers correspond with higher priority.', 'ad-code-manager' ); ?></p>
		<p><?php esc_html_e( 'Once you\'ve finished creating the ad codes, you can display them in your theme using:', 'ad-code-manager' ); ?></p>
		<ul>
			<li><?php echo wp_kses_post( __( 'a template tag in your theme: <code>&lt;?php do_action( \'acm_tag\', $tag_id ) ?></code>', 'ad-code-manager' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'a shortcode: <code>[acm-tag id="tag_id"]</code>', 'ad-code-manager' ) ); ?></li>
			<li><?php esc_html_e( 'or using a widget.', 'ad-code-manager' ); ?></li>
		</ul>
		<?php
		$configuration = ob_get_clean();

		ob_start();
		?>
		<p><?php esc_html_e( 'In the fields below, you can choose which conditionals you want. Some can take a value (i.e. define a specific category) in the second field.', 'ad-code-manager' ); ?></p>
		<p><?php esc_html_e( 'Here\'s an overview of the conditionals - they work the same as the functions of the same name in WordPress.', 'ad-code-manager' ); ?></p>
		<dl>
			<dt><a href="https://developer.wordpress.org/reference/functions/is_home/">is_home</a></dt>
			<dd><?php echo wp_kses_post( __( 'When the main blog page is being displayed. This is the page which shows the time based blog content of your site, so if you\'ve set a static Page for the Front Page (see below), then this will only be true on the Page which you set as the "Posts page" in <i>Settings &gt; Reading</i>.', 'ad-code-manager' ) ); ?></dd>

			<dt><a href="https://developer.wordpress.org/reference/functions/is_front_page">is_front_page</a></dt>
			<dd><?php echo wp_kses_post( __( 'When the front of the site is displayed, whether it is posts or a Page. Returns true when the main blog page is being displayed and the <i>Settings &gt; Reading &gt; Front page displays</i> is set to "Your latest posts", <b>or</b> when <i>Settings</a> &gt; Reading &gt; Front page displays</i> is set to "A static page" and the "Front Page" value is the current Page being displayed.', 'ad-code-manager' ) ); ?></dd>

			<dt><a href="https://developer.wordpress.org/reference/functions/is_category">is_category</a></dt>
			<dd><?php esc_html_e( 'When any Category archive page is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_category: 9</dt>
			<dd><?php esc_html_e( 'When the archive page for Category 9 is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_category: <?php esc_html_e( 'Stinky Cheeses', 'ad-code-manager' ); ?></dt>
			<dd><?php esc_html_e( 'When the archive page for the Category with Name "Stinky Cheeses" is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_category: <?php esc_html_e( 'blue-cheese', 'ad-code-manager' ); ?></dt>
			<dd><?php esc_html_e( 'When the archive page for the Category with Category Slug "blue-cheese" is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_category: <?php esc_html_e( 'array( 9, \'blue-cheese\', \'Stinky Cheeses\' )', 'ad-code-manager' ); ?></dt>
			<dd><?php echo wp_kses_post( __( 'Returns true when the category of posts being displayed is either term_ID 9, or <i>slug</i> "blue-cheese", or <i>name</i> "Stinky Cheeses".', 'ad-code-manager' ) ); ?></dd>

			<dt><a href="https://developer.wordpress.org/reference/functions/in_category/">in_category</a>: 5</dt>
			<dd><?php esc_html_e( 'Returns true if the current post is <b>in</b> the specified category id.', 'ad-code-manager' ); ?></dd>

			<dt><a href="https://developer.wordpress.org/reference/functions/is_tag">is_tag</a></dt>
			<dd><?php esc_html_e( 'When any Tag archive page is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_tag: mild</dt>
			<dd><?php esc_html_e( 'When the archive page for tag with the slug of "mild" is being displayed.', 'ad-code-manager' ); ?></dd>

			<dt>is_tag: array( 'sharp', 'mild', 'extreme' )</dt>
			<dd><?php esc_html_e( 'Returns true when the tag archive being displayed has a slug of either "sharp", "mild", or "extreme".', 'ad-code-manager' ); ?></dd>

			<dt>has_tag</dt>
			<dd><?php esc_html_e( 'When the current post has a tag. Must be used inside The Loop.', 'ad-code-manager' ); ?></dd>

			<dt>has_tag: mild</dt>
			<dd><?php esc_html_e( 'When the current post has the tag "mild".', 'ad-code-manager' ); ?></dd>

			<dt>has_tag: array( 'sharp', 'mild', 'extreme' )</dt>
			<dd><?php esc_html_e( 'When the current post has any of the tags in the array.', 'ad-code-manager' ); ?></dd>
		</dl>
		<?php
		$conditionals = ob_get_clean();

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'acm-overview',
				'title'   => __( 'Overview', 'ad-code-manager' ),
				'content' => $overview,
			)
		);
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'acm-config',
				'title'   => __( 'Configuration', 'ad-code-manager' ),
				'content' => $configuration,
			)
		);
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'acm-conditionals',
				'title'   => 'Conditionals',
				'content' => $conditionals,
			)
		);
	}
}
