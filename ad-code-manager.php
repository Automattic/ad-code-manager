<?php
/**
 * Ad Code Manager
 *
 * @package      Automattic\AdCodeManager
 * @author       Automattic and contributors
 * @copyright    2012 and later, Automattic and contributors
 * @license      GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Ad Code Manager
 * Plugin URI:        https://wordpress.org/plugins/ad-code-manager/
 * Description:       Easy ad code management.
 * Version:           0.6.0
 * Author:            Automattic and contributors
 * Author URI:        https://github.com/Automattic/ad-code-manager/graphs/contributors
 * Text Domain:       ad-code-manager
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/Automattic/ad-code-manager/
 * Requires PHP:      7.1
 * Requires WP:       5.5.0
 */

declare(strict_types=1);

namespace Automattic\AdCodeManager;

use Ad_Code_Manager;
use Automattic\AdCodeManager\UI\Contextual_Help;
use Automattic\AdCodeManager\UI\Plugin_Actions;

const AD_CODE_MANAGER_VERSION = '0.6.0';
const AD_CODE_MANAGER_FILE    = __FILE__;

require_once __DIR__ . '/src/class-acm-provider.php';
require_once __DIR__ . '/src/class-acm-wp-list-table.php';
require_once __DIR__ . '/src/class-acm-widget.php';
require_once __DIR__ . '/src/class-ad-code-manager.php';
require_once __DIR__ . '/src/UI/class-contextual-help.php';
require_once __DIR__ . '/src/UI/class-plugin-actions.php';

add_action(
	'plugins_loaded',
	function () {
		$GLOBALS['ad_code_manager'] = new Ad_Code_Manager();
		$GLOBALS['ad_code_manager']->run();
	}
);

add_action(
	'admin_init',
	function () {
		$contextual_help = new Contextual_Help();
		$contextual_help->run();

		$plugin_actions = new Plugin_Actions();
		$plugin_actions->run();
	}
);
