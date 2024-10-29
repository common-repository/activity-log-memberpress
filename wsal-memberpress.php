<?php
/**
 * Plugin Name: Activity Log for Memberpress
 * Plugin URI: https://melapress.com/wordpress-activity-log/
 * Description: A WP Activity Log plugin extension
 * Text Domain: wsal-memberpress
 * Author: Melapress
 * Author URI: http://melapress.com/
 * Version: 1.1.0
 * License: GPL2
 * Network: true
 *
 * @package Wsal
 * @subpackage Wsal Custom Events Loader
 */

use WSAL\Helpers\Classes_Helper;

/*
	Copyright(c) 2023  Melapress  (email : info@melapress.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
REQUIRED. Here we include and fire up the main core class. This will be needed regardless so be sure to leave line 37-39 in tact.
*/
require_once plugin_dir_path( __FILE__ ) . 'core/class-extension-core.php';
$wsal_extension = new WPWhiteSecurity\ActivityLog\Extensions\Common\Core( __FILE__, 'wsal-memberpress' );

// Used by event 9099.
if ( ! defined( 'WSAL_CLASS_PREFIX' ) ) {
	define( 'WSAL_CLASS_PREFIX', 'WSAL_' );
}

// Include extension specific functions.
require_once plugin_dir_path( __FILE__ ) . 'includes/wsal-functions.php';

add_action(
	'wsal_custom_alerts_register',
	/**
	* Adds sensors classes to the Class Helper
	*
	* @return void
	*
	* @since latest
	*/
	function () {
		require_once __DIR__ . '/wp-security-audit-log/class-memberpress-custom-alerts.php';

		Classes_Helper::add_to_class_map(
			array(
				'WSAL\\Custom_Alerts\\Memberpress_Custom_Alerts' => __DIR__ . '/wp-security-audit-log/class-memberpress-custom-alerts.php',
			)
		);
	}
);
