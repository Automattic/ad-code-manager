<?php

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration;

use WP_Error;

/**
 * Tests
 */
class AdCodeManagerTest extends TestCase {

	public function test_default_provider_slug() {
		self::assertEquals( 'doubleclick_for_publishers', $this->acm->current_provider_slug );
	}

	public function test_default_provider_is_an_object() {
		self::assertInstanceOf( 'ACM_Provider', $this->acm->current_provider );
	}

	public function test_providers_are_avaliable() {
		self::assertNotEmpty( $this->acm->providers );
	}

	public function test_create_proper_ad_code() {
		self::assertIsInt( $this->create_ad_code_and_return() );
	}

	public function test_get_ad_codes_after_create() {
		$this->create_ad_code_and_return();
		self::assertNotEmpty( $this->acm->get_ad_codes() );
	}

	public function test_edit_ad_code_not_all_required() {
		$ad_code = $this->mock_ad_code();
		array_shift( $ad_code );
		self::assertInstanceOf( WP_Error::class, $this->acm->edit_ad_code( 555, $ad_code ) );
	}

	public function test_edit_ad_code_proper() {
		self::assertIsInt( $this->acm->edit_ad_code( 555, $this->mock_ad_code() ) );
	}

	public function test_deleting_ad_requires_correct_post_type() {
		$ad_id   = $this->create_ad_code_and_return();
		$post_id = self::factory()->post->create();

		// Can delete the ad ID, because it is an ID with the correct custom post type.
		self::assertTrue( $this->acm->delete_ad_code( $ad_id ) );

		// Can't delete the general post ID, because it is not an ad post type.
		self::assertNull( $this->acm->delete_ad_code( $post_id ) );
	}

	private function mock_ad_code() {
		$ad_code = array();
		foreach ( $this->acm->current_provider->ad_code_args as $arg ) {
			$ad_code[ $arg['key'] ] = 'Column ' . $arg['key'] . ' , with label ' . $arg['label'];
		}
		$ad_code['priority'] = 10;
		$ad_code['operator'] = 'AND';
		return $ad_code;
	}

	private function create_ad_code_and_return() {
		return $this->acm->create_ad_code( $this->mock_ad_code() );
	}

	/**
	 * Test that ad output includes wrapper div with default classes.
	 *
	 * @covers Ad_Code_Manager::get_acm_tag
	 */
	public function test_get_acm_tag_includes_wrapper_with_default_classes(): void {
		// Create an ad code and register it.
		$ad_code_id = $this->create_ad_code_and_return();
		$this->acm->flush_cache();
		$this->acm->register_ad_codes( $this->acm->get_ad_codes() );

		// Get the first available tag.
		$test_tag = null;
		foreach ( $this->acm->ad_tag_ids as $tag ) {
			$test_tag = $tag['tag'];
			break;
		}

		if ( ! $test_tag ) {
			$this->markTestSkipped( 'No ad tags available for testing.' );
		}

		// Enable display of ad codes without conditionals.
		add_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );

		$output = $this->acm->get_acm_tag( $test_tag );

		remove_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );
		$this->acm->delete_ad_code( $ad_code_id );

		$this->assertStringContainsString( 'class="acm-wrapper', $output, 'Output should contain acm-wrapper class.' );
		$this->assertStringContainsString( 'acm-tag-' . sanitize_html_class( $test_tag ), $output, 'Output should contain tag-specific class.' );
	}

	/**
	 * Test that acm_wrapper_classes filter can modify wrapper classes.
	 *
	 * @covers Ad_Code_Manager::get_acm_tag
	 */
	public function test_acm_wrapper_classes_filter_modifies_classes(): void {
		// Create an ad code and register it.
		$ad_code_id = $this->create_ad_code_and_return();
		$this->acm->flush_cache();
		$this->acm->register_ad_codes( $this->acm->get_ad_codes() );

		// Get the first available tag.
		$test_tag = null;
		foreach ( $this->acm->ad_tag_ids as $tag ) {
			$test_tag = $tag['tag'];
			break;
		}

		if ( ! $test_tag ) {
			$this->markTestSkipped( 'No ad tags available for testing.' );
		}

		// Filter to add custom classes.
		$filter_callback = function ( $classes, $tag_id ) {
			$classes[] = 'custom-ad-class';
			$classes[] = 'another-class';
			return $classes;
		};

		add_filter( 'acm_wrapper_classes', $filter_callback, 10, 2 );
		add_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );

		$output = $this->acm->get_acm_tag( $test_tag );

		remove_filter( 'acm_wrapper_classes', $filter_callback, 10 );
		remove_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );
		$this->acm->delete_ad_code( $ad_code_id );

		$this->assertStringContainsString( 'custom-ad-class', $output, 'Output should contain custom class.' );
		$this->assertStringContainsString( 'another-class', $output, 'Output should contain another custom class.' );
	}

	/**
	 * Test that acm_wrapper_classes filter can disable wrapper.
	 *
	 * @covers Ad_Code_Manager::get_acm_tag
	 */
	public function test_acm_wrapper_classes_filter_can_disable_wrapper(): void {
		// Create an ad code and register it.
		$ad_code_id = $this->create_ad_code_and_return();
		$this->acm->flush_cache();
		$this->acm->register_ad_codes( $this->acm->get_ad_codes() );

		// Get the first available tag.
		$test_tag = null;
		foreach ( $this->acm->ad_tag_ids as $tag ) {
			$test_tag = $tag['tag'];
			break;
		}

		if ( ! $test_tag ) {
			$this->markTestSkipped( 'No ad tags available for testing.' );
		}

		// Filter to return empty array (disables wrapper).
		add_filter( 'acm_wrapper_classes', '__return_empty_array' );
		add_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );

		$output = $this->acm->get_acm_tag( $test_tag );

		remove_filter( 'acm_wrapper_classes', '__return_empty_array' );
		remove_filter( 'acm_display_ad_codes_without_conditionals', '__return_true' );
		$this->acm->delete_ad_code( $ad_code_id );

		$this->assertStringNotContainsString( 'acm-wrapper', $output, 'Output should not contain wrapper when filter returns empty array.' );
	}
}
