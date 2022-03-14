<?php

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration;

use WP_Error;

/**
 * Tests
 */
class AdCodeManagerTest extends TestCase {

	public function test_default_provider_slug() {
		self::assertEquals( 'doubleclick_for_publishers',  $this->acm->current_provider_slug );
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
		array_shift($ad_code);
		self::assertInstanceOf( WP_Error::class, $this->acm->edit_ad_code( 555, $ad_code ) );
	}

	public function test_edit_ad_code_proper() {
		self::assertIsInt( $this->acm->edit_ad_code( 555,  $this->mock_ad_code() ) );
	}

	private function mock_ad_code() {
		$ad_code = array();
		foreach ( $this->acm->current_provider->ad_code_args as $arg ) {
			$ad_code[$arg['key']] = 'Column ' . $arg['key'] . ' , with label ' . $arg['label'] ;
		}
		$ad_code['priority'] = 10;
		$ad_code['operator'] = 'AND';
		return $ad_code;
	}

	private function create_ad_code_and_return() {
		return $this->acm->create_ad_code( $this->mock_ad_code() );
	}
}
