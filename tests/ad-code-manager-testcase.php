<?php

/**
 * Base unit test class for Ad Code manager
 */
class AdCodeManager_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $ad_code_manager;
		$this->_toc = $ad_code_manager;
	}
}
