<?php
/**
 * Plugin Name:   Advanced Password Security
 * Description:   Force your admins and/or select roles to update their password to a new one after a set amount of time.
 * Author:        Trew Knowledge
 * Author URI:    http://trewknowledge.com
 * Version:       1.0.3
 * Plugin URI:    http://github.com/trewknowledge/advanced-password-security
 * License:       GPL-3.0+
 * License URI:   http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:   /i18n
 * Text Domain: 	aps
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
