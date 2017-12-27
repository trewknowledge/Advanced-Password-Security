<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'APS_Settings' ) ) {

	class APS_Settings {

		private static $views_dir;
		private static $instance;


		public function __construct() {
			self::$views_dir = plugin_dir_path( APS_PLUGIN_FILE ) . '/includes/settings/views';
			self::hooks();
			self::save();
		}

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		private static function hooks() {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}

		public static function admin_menu() {
			add_menu_page(
				__( 'APS', 'aps' ),
				__( 'APS', 'aps' ),
				'manage_options',
				'aps',
				array( __CLASS__, 'output' ),
				'dashicons-admin-network'
			);
		}

		public static function output() {
			include_once self::$views_dir . '/html-admin-settings.php';
		}

		private static function save() {

			if ( ! is_admin() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( isset( $_POST['aps_settings'] ) ) {
				$settings = get_option( 'aps_settings' );
				$settings = ( isset( $settings ) && $settings ) ? $settings : array();

				$reset_in = ( isset( $_POST['aps_settings']['reset_in'] ) ) ? wp_unslash( absint( $_POST['aps_settings']['reset_in'] ) ) : $settings['reset_in'];
				$roles    = ( isset( $_POST['aps_settings']['roles'] ) ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['aps_settings']['roles'] ) ) : $settings['roles'];

				$roles[] = 'administrator';
				$new_settings = array(
					'reset_in' => $reset_in,
					'roles'    => $roles,
				);

				$new_value = array_merge( $settings, $new_settings );
				update_option( 'aps_settings', $new_value );
			}
		}

	}

}

function APS_Settings() {
	return APS_Settings::get_instance();
}

add_action( 'plugins_loaded', 'APS_Settings' );
