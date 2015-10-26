<?php
/*
Plugin Name: Advanced Password Security
Version: 1.0
Description: Used to re-inforce security forcing users to reset their passwords after X days. They also can't use a previously used password.
Author: Trew Knowledge
Author URI: http://trewknowledge.com
Plugin URI: http://trewknowledge.com
Text Domain: tk-advanced-password-security
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APS_PLUGIN', 		plugin_basename( __FILE__ ) );
define( 'APS_DIR', 			plugin_dir_path( __FILE__ ) );
define( 'APS_URL', 			plugin_dir_url( __FILE__ ) );
define( 'APS_INC_DIR',		APS_DIR . 'includes/' );
define( 'APS_TEXTDOMAIN', 	'tk-advanced-password-security' );
define( 'APS_LANG_PATH', 	dirname( APS_PLUGIN ) . '/languages' );

final class Advanced_Password_Security {

	/**
	 * User meta key identifier
	 * @var string
	 */
	const META_KEY = 'aps_password_reset';

	/**
	 * Plugin instance
	 * @var object
	 */
	private static $_instance;

	/**
	 * Generic prefix/key identifier
	 * @var string
	 */
	public static $prefix = 'aps_';

	/**
	 * Stores a list of users
	 */
	private $users;

	/**
	 * Local instance of $wpdb
	 */
	private $db;

	/**
	 * Class Constructor
	 */
	private function __construct() {
		global $wpdb;

		$this->users = get_users( array( 'fields' => array( 'ID', 'user_pass' ) ) );
		$this->db = $wpdb;

		foreach ( glob( APS_INC_DIR . '*.php' ) as $include ) {
			if ( is_readable( $include ) ) {
				require_once $include;
			}
		}

		$this->hooks();

	}

	private function hooks() {
		register_activation_hook( __FILE__, 	array( $this, 'activation' ) );
		add_action( 'wp_enqueue_scripts',		array( $this, 'load_assets' ) );
		add_action( 'admin_enqueue_scripts',	array( $this, 'load_assets' ) );
		add_action( 'init', 					array( $this, 'init' ) );
		add_action( 'user_register', 			array( $this, 'new_user_registration' ), 10, 1 );
		$this->ajax();
	}

	public function load_assets() {
		wp_enqueue_style( 'aps_css_style', 	APS_URL . 'assets/css/advanced-password-security.css' );
		wp_enqueue_script( 'aps_js', 		APS_URL . 'assets/js/advanced-password-security.js', array( 'jquery' ), false, true );

		wp_localize_script( 'aps_js', 'APS_Ajax', array(
		        'ajaxurl' => admin_url( 'admin-ajax.php' ),
		        'loginurl' => wp_login_url(),
		        'ajaxnonce' => wp_create_nonce( 'aps-ticket' )
	        )
	    );
	}

	private function ajax() {
		add_action( 'wp_ajax_reset-all-passwords', 			array( $this, 'reset_all_users' ) );		
	}

	public static function reset_all_users() {
		check_ajax_referer( 'aps-ticket', 'ticket' );
		$users = get_users( array( 'fields' => array( 'ID', 'user_pass' ) ) );
		foreach ( $users as $user ) {
			update_user_meta( $user->ID, self::META_KEY, 1 );
		}

		WP_Session_Tokens::destroy_all_for_all_users();
	    wp_logout();
		wp_die();
	}

	/**
	 * Load translations
	 * @return null
	 */
	public static function i18n() {
		load_plugin_textdomain( APS_TEXTDOMAIN, false, APS_LANG_PATH );
	}

	/**
	 * Get plugin instance
	 * @return object
	 */
	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init() {
		self::i18n();

		new Advanced_Password_Security\Login;

		if ( ! is_user_logged_in() ) {
			return;
		}

		new Advanced_Password_Security\Settings;
	}

	function activation() {
		$this->create_db_table_column();
		add_option( self::$prefix . 'settings', array( 'limit' => 30, 'save_old_passwords' => true ) );
		foreach ( $this->users as $user ) {
			if ( !get_user_meta( $user->ID, self::META_KEY, true ) ) {
				add_user_meta( $user->ID, self::META_KEY, date( "U" ) );				
			}
		}
	}

	function new_user_registration( $user_id ) {
		if ( !get_user_meta( $user_id, self::META_KEY, true ) ) {
			add_user_meta( $user_id, self::META_KEY, date( "U" ) );				
		}
	}

	function create_db_table_column(){
		$sql = $this->db->prepare(
			"
			SELECT COLUMN_NAME
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = %s
			AND table_schema = %s
			AND column_name = %s
			",
			$this->db->users,
			$this->db->dbname,
			'old_user_pass'
		);

		$column_exists = $this->db->get_var($sql);
		if ( empty($column_exists) ) {
			$this->db->query(
				"
				ALTER TABLE {$this->db->users}
				ADD old_user_pass LONGTEXT
				"
			);			
		}
	}

	public static function get_limit() {
		$options = get_option( self::$prefix . 'settings' );
		return absint( $options['limit'] );
	}

	public static function get_expiration_date( $user = null ) {
		if ( is_int( $user ) ) {
			$user_id = $user;
		}else if ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;
		}else{
			$user_id = wp_get_current_user()->ID;
		}
		
		if ( !get_userdata( $user_id ) ) {
			return new WP_Error( 'User does not exist', esc_html__( 'User does not exist', APS_TEXTDOMAIN ) );
		}

		$last_reset = get_user_meta( $user_id, self::META_KEY, true );
		$expires = strtotime( sprintf( '@%d + %d days', $last_reset, self::get_limit() ) );

		return date( 'U', $expires );
	}

	public static function is_password_expired( $user = null ) {
		if ( is_int( $user ) ) {
			$user_id = $user;
		}else if ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;
		}else{
			$user_id = wp_get_current_user()->ID;
		}

		if ( !get_userdata( $user_id ) ) {
			return new WP_Error( 'User does not exist', esc_html__( 'User does not exist', APS_TEXTDOMAIN ) );
		}

		$time = get_user_meta( $user_id, self::META_KEY, true );
		$limit = self::get_limit();
		
		$expires = self::get_expiration_date( $user_id );

		return ( time() > $expires );
	}

	public static function get_old_passwords( $user ) {
		if ( is_int( $user ) ) {
			$userObj = get_userdata( $user );
		}else if ( is_a( $user, 'WP_User' ) ) {
			$userObj = $user;
		}else{
			$userObj = wp_get_current_user();
		}

		$used_passwords = maybe_unserialize( $userObj->data->old_user_pass );

		if ( !empty( $used_passwords ) && is_array( $used_passwords ) ) {
			return $used_passwords;
		}

		return array();
	}

	public static function should_save_old_passwords() {
		$options = get_option( self::$prefix . 'settings' );
		$value   = isset( $options['save_old_passwords'] ) ? $options['save_old_passwords'] : null;

		return isset( $options['save_old_passwords'] ) ? true : false;
	}

	public static function get_countdown( $user = null ) {
		if ( is_int( $user ) ) {
			$user_id = $user;
		}else if ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;
		}else{
			$user_id = wp_get_current_user()->ID;
		}

		$expires = self::get_expiration_date( $user_id );
		$diff = $expires - time();
		return floor( $diff / ( 60 * 60 * 24 ) );
	}

}

if ( class_exists( 'Advanced_Password_Security' ) ) {
	Advanced_Password_Security::instance();
}
