<?php

declare(strict_types=1);

namespace Automattic\AdCodeManager\Tests\Integration;

use Yoast\WPTestUtils\WPIntegration\TestCase as WPIntegrationTestCase;

/**
 * Base unit test class for Ad Code manager
 */
abstract class TestCase extends WPIntegrationTestCase {

	public $acm;

	public function set_up() {
		parent::set_up();
		$this->acm = $GLOBALS['ad_code_manager'];
		$this->acm->action_load_providers();
		$this->acm->action_init();
	}
}
