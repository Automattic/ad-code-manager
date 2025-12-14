<?php
/**
 * Unit tests for the Ad_Code_Manager class.
 *
 * @package Automattic\AdCodeManager\Tests\Unit
 */

declare( strict_types=1 );

namespace Automattic\AdCodeManager\Tests\Unit;

use Ad_Code_Manager;
use Brain\Monkey\Functions;
use stdClass;

/**
 * Test case for Ad_Code_Manager.
 */
class AdCodeManagerTest extends TestCase {

	/**
	 * The Ad_Code_Manager instance.
	 *
	 * @var Ad_Code_Manager
	 */
	private Ad_Code_Manager $ad_code_manager;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Stub WordPress functions.
		Functions\stubs( array( '__' => null ) );

		$this->ad_code_manager = new Ad_Code_Manager();

		// Create a mock provider with whitelisted URLs.
		$this->ad_code_manager->current_provider                          = new stdClass();
		$this->ad_code_manager->current_provider->whitelisted_script_urls = array(
			'example.com',
			'ads.google.com',
			'secure.pagead2.googlesyndication.com',
		);
	}

	/**
	 * Test validate_script_url with empty URL returns true.
	 */
	public function testValidateScriptUrlEmptyReturnsTrue(): void {
		$this->assertTrue( $this->ad_code_manager->validate_script_url( '' ) );
	}

	/**
	 * Test validate_script_url with exact domain match.
	 */
	public function testValidateScriptUrlExactDomainMatch(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertTrue(
			$this->ad_code_manager->validate_script_url( 'https://example.com/script.js' )
		);
	}

	/**
	 * Test validate_script_url with subdomain match.
	 */
	public function testValidateScriptUrlSubdomainMatch(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertTrue(
			$this->ad_code_manager->validate_script_url( 'https://cdn.example.com/ads/script.js' )
		);
	}

	/**
	 * Test validate_script_url with non-whitelisted domain.
	 */
	public function testValidateScriptUrlNonWhitelistedDomain(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertFalse(
			$this->ad_code_manager->validate_script_url( 'https://malicious-site.com/script.js' )
		);
	}

	/**
	 * Test validate_script_url prevents domain spoofing.
	 *
	 * Ensures that 'evilexample.com' doesn't match 'example.com'.
	 */
	public function testValidateScriptUrlPreventsDomainSpoofing(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertFalse(
			$this->ad_code_manager->validate_script_url( 'https://evilexample.com/script.js' )
		);
	}

	/**
	 * Test validate_script_url with Google Ads URL.
	 */
	public function testValidateScriptUrlGoogleAds(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertTrue(
			$this->ad_code_manager->validate_script_url( 'https://ads.google.com/ad-manager/tag.js' )
		);
	}

	/**
	 * Test validate_script_url with deep subdomain.
	 */
	public function testValidateScriptUrlDeepSubdomain(): void {
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );

		$this->assertTrue(
			$this->ad_code_manager->validate_script_url( 'https://a.b.c.example.com/script.js' )
		);
	}

	/**
	 * Test filter_output_tokens adds URL vars as tokens.
	 */
	public function testFilterOutputTokensAddsUrlVars(): void {
		$code_to_display = array(
			'url_vars' => array(
				'site_id' => '12345',
				'zone'    => 'header',
				'width'   => '728',
				'height'  => '90',
			),
		);

		$output_tokens = $this->ad_code_manager->filter_output_tokens( array(), 'test_tag', $code_to_display );

		$this->assertArrayHasKey( '%site_id%', $output_tokens );
		$this->assertArrayHasKey( '%zone%', $output_tokens );
		$this->assertArrayHasKey( '%width%', $output_tokens );
		$this->assertArrayHasKey( '%height%', $output_tokens );
		$this->assertSame( '12345', $output_tokens['%site_id%'] );
		$this->assertSame( 'header', $output_tokens['%zone%'] );
	}

	/**
	 * Test filter_output_tokens returns original tokens when no URL vars.
	 */
	public function testFilterOutputTokensReturnsOriginalWhenNoUrlVars(): void {
		$code_to_display = array();
		$original_tokens = array( '%existing%' => 'value' );

		$output_tokens = $this->ad_code_manager->filter_output_tokens(
			$original_tokens,
			'test_tag',
			$code_to_display
		);

		$this->assertSame( $original_tokens, $output_tokens );
	}

	/**
	 * Test filter_output_tokens preserves existing tokens.
	 */
	public function testFilterOutputTokensPreservesExistingTokens(): void {
		$code_to_display = array(
			'url_vars' => array(
				'new_var' => 'new_value',
			),
		);
		$original_tokens = array( '%existing%' => 'value' );

		$output_tokens = $this->ad_code_manager->filter_output_tokens(
			$original_tokens,
			'test_tag',
			$code_to_display
		);

		$this->assertArrayHasKey( '%existing%', $output_tokens );
		$this->assertArrayHasKey( '%new_var%', $output_tokens );
	}

	/**
	 * Test get_acm_tag returns empty string when ad rendering is disabled.
	 *
	 * @covers Ad_Code_Manager::get_acm_tag
	 */
	public function testGetAcmTagReturnsEmptyWhenRenderingDisabled(): void {
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter_name, $value ) {
				if ( 'acm_disable_ad_rendering' === $filter_name ) {
					return true; // Disable rendering.
				}
				return $value;
			}
		);

		$result = $this->ad_code_manager->get_acm_tag( 'test_tag' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test get_acm_tag returns empty string when no ad codes registered for tag.
	 *
	 * This verifies the fix for issue #72 - no broken HTML output when no ad codes found.
	 *
	 * @covers Ad_Code_Manager::get_acm_tag
	 */
	public function testGetAcmTagReturnsEmptyWhenNoAdCodesRegistered(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( $filter_name, $value ) {
				if ( 'acm_disable_ad_rendering' === $filter_name ) {
					return false; // Rendering enabled.
				}
				return $value;
			}
		);
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_add' )->justReturn( true );

		// Ensure no ad codes are registered for this tag.
		$this->assertArrayNotHasKey( 'nonexistent_tag', $this->ad_code_manager->ad_codes );

		$result = $this->ad_code_manager->get_acm_tag( 'nonexistent_tag' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test get_matching_ad_code returns null when tag has no registered ad codes.
	 *
	 * @covers Ad_Code_Manager::get_matching_ad_code
	 */
	public function testGetMatchingAdCodeReturnsNullWhenNoAdCodes(): void {
		$result = $this->ad_code_manager->get_matching_ad_code( 'nonexistent_tag' );

		$this->assertNull( $result );
	}
}
