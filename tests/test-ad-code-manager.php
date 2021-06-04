<?php

/**
 * Tests
 */
class Test_AdCodeManager extends AdCodeManager_TestCase {


	public function test__DefaultProviderSlug() {
		$this->assertEquals( 'doubleclick_for_publishers',  $this->acm->current_provider_slug );
	}

	public function test__ProviderIsObject() {
		$this->assertInstanceOf( 'ACM_Provider', $this->acm->current_provider );
	}

	public function test__ProvidersAreAvaliable() {
		$this->assertNotEmpty( $this->acm->providers );
	}

	public function test__CreateProperAdCode3() {
		$this->assertInternalType( 'int', $this->_createAdCodeAndReturn() );
	}

	public function test__GetAdCodesAfterCreate() {
		$this->_createAdCodeAndReturn();
		$this->assertNotEmpty( $this->acm->get_ad_codes() );
	}

	public function test__EditAdCodeNotAllRequired() {
		$ad_code = $this->_mockAdCode();
		array_shift($ad_code);
		$this->assertInstanceOf('WP_Error', $this->acm->edit_ad_code( 555, $ad_code ) );
	}

	public function test__EditAdCodeProper() {
		$this->assertInternalType('int', $this->acm->edit_ad_code( 555,  $this->_mockAdCode() ) );
	}

	private function _mockAdCode() {
		$ad_code = array();
		foreach ( $this->acm->current_provider->ad_code_args as $arg ) {
			$ad_code[$arg['key']] = "Column " . $arg['key']. " , with label ". $arg['label'] ;
		}
		$ad_code['priority'] = 10;
		$ad_code['operator'] = 'AND';
		return $ad_code;
	}

	private function _createAdCodeAndReturn() {
		return $this->acm->create_ad_code( $this->_mockAdCode() );
	}
}
