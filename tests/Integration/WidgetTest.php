<?php
/**
 * Integration tests for the ACM_Ad_Zones widget.
 *
 * Tests for issue #72: Ad code tag still rendered if no valid ad codes found.
 *
 * @package Automattic\AdCodeManager\Tests\Integration
 */

declare( strict_types=1 );

namespace Automattic\AdCodeManager\Tests\Integration;

use ACM_Ad_Zones;

/**
 * Test case for ACM_Ad_Zones widget.
 */
class WidgetTest extends TestCase {

	/**
	 * The widget instance.
	 *
	 * @var ACM_Ad_Zones
	 */
	private ACM_Ad_Zones $widget;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->widget = new ACM_Ad_Zones();
	}

	/**
	 * Test widget outputs nothing when no ad code is found.
	 *
	 * This is the main fix for issue #72 - the widget should not output
	 * empty wrapper HTML when there's no ad content to display.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_widget_outputs_nothing_when_no_ad_code_found(): void {
		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => 'Test Ad',
			'ad_zone' => 'nonexistent_zone',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Widget should output nothing when no ad code is found.' );
	}

	/**
	 * Test widget outputs nothing for empty ad zone.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_widget_outputs_nothing_for_empty_ad_zone(): void {
		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => '',
			'ad_zone' => '',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Widget should output nothing for empty ad zone.' );
	}

	/**
	 * Test widget outputs wrapper when filter returns true for empty content.
	 *
	 * The acm_display_empty_widget filter allows themes to force display
	 * of the widget wrapper even when no ad content is found.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_widget_outputs_wrapper_when_filter_returns_true(): void {
		add_filter( 'acm_display_empty_widget', '__return_true' );

		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => '',
			'ad_zone' => 'nonexistent_zone',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		$output = ob_get_clean();

		remove_filter( 'acm_display_empty_widget', '__return_true' );

		$this->assertStringContainsString( '<div class="widget">', $output, 'Widget should output wrapper when filter returns true.' );
		$this->assertStringContainsString( '</div>', $output, 'Widget should output closing wrapper when filter returns true.' );
	}

	/**
	 * Test widget outputs title when filter allows empty content.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_widget_outputs_title_when_filter_allows_empty_content(): void {
		add_filter( 'acm_display_empty_widget', '__return_true' );

		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => 'Advertisement',
			'ad_zone' => 'nonexistent_zone',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		$output = ob_get_clean();

		remove_filter( 'acm_display_empty_widget', '__return_true' );

		$this->assertStringContainsString( '<h2>Advertisement</h2>', $output, 'Widget should output title when filter allows.' );
	}

	/**
	 * Test acm_display_empty_widget filter receives correct arguments.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_filter_receives_correct_arguments(): void {
		$received_args = array();

		$filter_callback = function ( $display, $ad_zone, $args, $instance ) use ( &$received_args ) {
			$received_args = array(
				'display'  => $display,
				'ad_zone'  => $ad_zone,
				'args'     => $args,
				'instance' => $instance,
			);
			return false;
		};

		add_filter( 'acm_display_empty_widget', $filter_callback, 10, 4 );

		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => 'Test',
			'ad_zone' => 'test_zone',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		ob_get_clean();

		remove_filter( 'acm_display_empty_widget', $filter_callback, 10 );

		$this->assertFalse( $received_args['display'], 'First argument should be false.' );
		$this->assertSame( 'test_zone', $received_args['ad_zone'], 'Second argument should be the ad zone.' );
		$this->assertSame( $args, $received_args['args'], 'Third argument should be widget args.' );
		$this->assertSame( $instance, $received_args['instance'], 'Fourth argument should be widget instance.' );
	}

	/**
	 * Test widget with valid ad code outputs content.
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_widget_outputs_content_with_valid_ad_code(): void {
		// Create a valid ad code.
		$ad_code_data = array();
		foreach ( $this->acm->current_provider->ad_code_args as $arg ) {
			$ad_code_data[ $arg['key'] ] = 'test_value_' . $arg['key'];
		}
		$ad_code_data['priority'] = 10;
		$ad_code_data['operator'] = 'AND';

		// Need to set up a tag that's registered.
		// First, let's check what tags are available.
		$ad_tag_ids = $this->acm->ad_tag_ids;
		if ( empty( $ad_tag_ids ) ) {
			$this->markTestSkipped( 'No ad tag IDs available for testing.' );
		}

		// Get the first tag with enable_ui_mapping.
		$test_tag = null;
		foreach ( $ad_tag_ids as $tag ) {
			if ( isset( $tag['enable_ui_mapping'] ) && $tag['enable_ui_mapping'] ) {
				$test_tag = $tag['tag'];
				break;
			}
		}

		if ( ! $test_tag ) {
			$this->markTestSkipped( 'No tags with enable_ui_mapping available for testing.' );
		}

		// Set the tag in ad code data.
		$ad_code_data['tag'] = $test_tag;

		// Create the ad code.
		$ad_code_id = $this->acm->create_ad_code( $ad_code_data );
		$this->assertIsInt( $ad_code_id, 'Ad code should be created successfully.' );

		// Register ad codes to make them available.
		$this->acm->register_ad_codes( $this->acm->get_ad_codes() );

		// Enable display of ad codes without conditionals.
		add_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );

		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => 'Ad Widget',
			'ad_zone' => $test_tag,
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		$output = ob_get_clean();

		remove_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );

		// Clean up.
		$this->acm->delete_ad_code( $ad_code_id );

		$this->assertStringContainsString( '<div class="widget">', $output, 'Widget should output wrapper with valid ad code.' );
		$this->assertStringContainsString( '</div>', $output, 'Widget should output closing wrapper.' );
	}

	/**
	 * Test that acm_tag action is still fired (backwards compatibility).
	 *
	 * @covers ACM_Ad_Zones::widget
	 */
	public function test_acm_tag_action_is_fired(): void {
		$action_fired = false;
		$action_tag   = null;

		$action_callback = function ( $tag_id ) use ( &$action_fired, &$action_tag ) {
			$action_fired = true;
			$action_tag   = $tag_id;
		};

		add_action( 'acm_tag', $action_callback, 5 ); // Priority 5 to run before the default handler.

		$args     = array(
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'   => '',
			'ad_zone' => 'test_action_zone',
		);

		ob_start();
		$this->widget->widget( $args, $instance );
		ob_get_clean();

		remove_action( 'acm_tag', $action_callback, 5 );

		$this->assertTrue( $action_fired, 'acm_tag action should be fired for backwards compatibility.' );
		$this->assertSame( 'test_action_zone', $action_tag, 'acm_tag action should receive the correct tag ID.' );
	}
}
