=== Ad Code Manager ===
Contributors: danielbachhuber, rinatkhaziev, zztimur, jeremyfelt, automattic, doejo
Tags: advertising, ad codes
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 0.1.3

Manage your ad codes through the WordPress admin in a safe and easy way.

== Description ==

Ad Code Manager gives non-developers an interface in the WordPress admin for configuring your complex set of ad codes.

To set things up, you'll need to add small template tags to your theme where you'd like your ads to appear. These are called "ad tags." Then, you'll need to define a common set of parameters for your ad provider. These parameters include all of the tag IDs you've established in your template, the default script URL, the default output, etc.

Once this code-level configuration is in place, the Ad Code Manager admin interface will allow you to add new ad codes, modify the parameters for your script URL, and define conditionals to determine when the ad code appears. Conditionals are core WordPress functions like is_page(), is_category(), or your own custom functions.

Ad Code Manager currently works with Doubleclick for Publishers and [we'd like to abstract it to other providers](https://github.com/Automattic/Ad-Code-Manager/issues/4)

[Fork the plugin on Github](https://github.com/Automattic/Ad-Code-Manager) and [follow our development blog](http://adcodemanager.wordpress.com/).

== Installation ==

Since the plugin is in its early stages, there are a couple additional configuration steps:

1. Upload `ad-code-manager` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Incorporate ad tags in your theme template with <?php do_action( 'acm_tag', 'slot' ) ?>. Also you can use [acm-tag id="slot"] shorcode or ACM Widget
1. Implement filters to make the plugin work with your provider
1. Configure your ad codes in the WordPress admin ( Tools -> Ad Code Manager )

== Configuration Filters ==

There are some filters which will allow you to easily customize output of the plugin. You should place these filters in your themes functions.php file or someplace safe.

[Check out this gist](https://gist.github.com/1631131) to see all of the filters in action.

= acm_default_url =

Currently, we don't store tokenized script URL anywhere so this filter is a nice place to set default value.

Arguments:
* string $url The tokenized url of Ad Code

Example usage: Set your default ad code URL

`add_filter( 'acm_default_url', 'my_acm_default_url' );
function my_acm_default_url( $url ) {
	if ( 0 === strlen( $url )  ) {
		return "http://ad.doubleclick.net/adj/%site_name%/%zone1%;s1=%zone1%;s2=;pid=%permalink%;fold=%fold%;kw=;test=%test%;ltv=ad;pos=%pos%;dcopt=%dcopt%;tile=%tile%;sz=%sz%;";
	}
}`

= acm_output_tokens =

Register output tokens depending on the needs of your setup. Tokens are the keys to be replaced in your script URL.

Arguments:
* array $output_tokens Any existing output tokens
* string $tag_id Unique tag id 
* array $code_to_display Ad Code that matched conditionals

Example usage: Test to determine whether you're in test or production by passing ?test=on query argument

`add_filter( 'acm_output_tokens', 'my_acm_output_tokens', 10, 3 );
function my_acm_output_tokens( $output_tokens, $tag_id, $code_to_display ) {
	$output_tokens['%test%'] = isset( $_GET['test'] ) && $_GET['test'] == 'on' ? 'on' : '';
	return $output_tokens;
}`

= acm_ad_tag_ids =

Extend set of default tag ids. Ad tag ids are used as a parameter for your template tag (e.g. do_action( 'acm_tag', 'my_top_leaderboard' ))
 
Arguments:
* array $tag_ids array of default tag ids

Example usage: Add a new ad tag called 'my_top_leaderboard'

`add_filter( 'acm_ad_tag_ids', 'my_acm_ad_tag_ids' );
function my_acm_ad_tag_ids( $tag_ids ) {
	$tag_ids[] = array(
		'tag' => 'my_top_leaderboard', // tag_id 
		'url_vars' => array(
			'sz' => '728x90', // %sz% token
			'fold' => 'atf', // %fold% token
			'my_custom_token' => 'something' // %my_custom_token% will be replaced with 'something'
		);
	return $tag_ids;
}`

= acm_output_html =

Support multiple ad formats ( e.g. Javascript ad tags, or simple HTML tags ) by adjusting the HTML rendered for a given ad tag.

Arguments:
* string $output_html The original output HTML
* string $tag_id Ad tag currently being accessed

Example usage:

`add_filter( 'acm_output_html', 'my_acm_output_html', 10, 2 );
function my_acm_output_html( $output_html, $tag_id ) {
	switch ( $tag_id ) {
		case 'my_leaderboard':
			$output_html = '<a href="%url%"><img src="%image_url%" /></a>';
			break;
		case 'rich_media_leaderboard':
			$output_html = '<script> // omitted </script>';
			break;
		default:
			break;
	}
	return $output_html;
}`

= acm_whitelisted_conditionals =

Extend the list of usable conditional functions with your own awesome ones. We whitelist these so users can't execute random PHP functions.

Arguments: 
* array $conditionals Default conditionals

Example usage: Register a few custom conditional callbacks

`add_filter( 'acm_whitelisted_conditionals', 'my_acm_whitelisted_conditionals' );
function my_acm_whitelisted_conditionals( $conditionals ) {
	$conditionals[] = 'my_is_post_type';
	$conditionals[] = 'is_post_type_archive';
	$conditionals[] = 'my_page_is_child_of';
	return $conditionals;
}`

= acm_conditional_args =

For certain conditionals (has_tag, has_category), you might need to pass additional arguments.

Arguments:
* array $cond_args Existing conditional arguments
* string $cond_func Conditional function (is_category, is_page, etc)

Example usage: has_category() and has_tag() use has_term(), which requires the object ID to function properly

`add_filter( 'acm_conditional_args', 'my_acm_conditional_args', 10, 2 );
function my_acm_conditional_args( $cond_args, $cond_func ) {
	global $wp_query;
	// has_category and has_tag use has_term
	// we should pass queried object id for it to produce correct result
	if ( in_array( $cond_func, array( 'has_category', 'has_tag' ) ) ) {
		if ( $wp_query->is_single == true ) {
			$cond_args[] = $wp_query->queried_object->ID;
		}
	}
	// my_page_is_child_of is our custom WP conditional tag and we have to pass queried object ID to it
	if ( in_array( $cond_func, array( 'my_page_is_child_of' ) ) && $wp_query->is_page ) {
		$cond_args[] = $cond_args[] = $wp_query->queried_object->ID;
	}

	return $cond_args;
}`

= acm_whitelisted_script_urls =

A security filter to whitelist which ad code script URLs can be added in the admin

Arguments:
* array $whitelisted_urls Existing whitelisted ad code URLs

Example usage: Allow Doubleclick for Publishers ad codes to be used

`add_filter( 'acm_whitelisted_script_urls', 'my_acm_whitelisted_script_urls' );
function my_acm_whiltelisted_script_urls( $whitelisted_urls ) {
	$whitelisted_urls = array( 'ad.doubleclick.net' );
	return $whitelisted_urls;
}`

= acm_display_ad_codes_without_conditionals =

Change the behavior of Ad Code Manager so that ad codes without conditionals display on the frontend. The default behavior is that each ad code requires a conditional to be included in the presentation logic.

Arguments:
* bool $behavior Whether or not to display the ad codes that don't have conditionals

Example usage:

`add_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );`

== Changelog ==

= 0.1.3 (February 13, 2012) =
* UI cleanup for the admin, including styling and information on applying conditionals

= 0.1.2 (February 9, 2012) =
* Readme with full description and examples
* Bug fix: Save the proper value when editing actions

= 0.1.1 =
* Bug fix release

= 0.1 =
* Initial release