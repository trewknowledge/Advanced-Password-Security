<?php
/**
 * Plugin Name:   Advanced Password Security
 * Description:   This plugin make sure your admins and/or others change their password from time to time. They also can't use a previously used password.
 * Author:        Trew Knowledge
 * Author URI:    http://trewknowledge.com
 * Version:       1.0.0
 * Plugin URI:    http://github.com/trewknowledge/advanced-password-security
 * License:       GPL-2.0+
 * License URI:   http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:   /i18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'APS_PLUGIN_FILE' ) ) {
	define( 'APS_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'APS' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-aps.php';
}

function APS() {
	return APS::get_instance();
}

add_action( 'plugins_loaded', 'APS' );
