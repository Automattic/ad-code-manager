<?php

/**
 * Base unit test class for Ad Code manager
 */
class AdCodeManager_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		$this->acm = $GLOBALS['ad_code_manager'];
		$this->acm->action_load_providers();
		$this->acm->action_init();
	}
}
