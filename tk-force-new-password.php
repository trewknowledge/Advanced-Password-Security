<?php
/*
Plugin Name: TK Force New Password
Version: 0.1.0
Description: Used to re-inforce security forcing users to reset their passwords after X days. They also can't use a previously used password.
Author: Trew Knowledge
Author URI: http://trewknowledge.com
Plugin URI: http://trewknowledge.com
Text Domain: tk-force-password
Domain Path: /i18n
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class TK_Force_New_Password {

	private $db;
	private $table;
	private $users;
	private $hasher;
	private $days_before_update;

	public $found_pass;

	private static $instance;

	function __construct(){
		global $wpdb;
		global $wp_hasher;

		if ( empty($wp_hasher) ) {
            require_once( ABSPATH . WPINC . '/class-phpass.php');
            // By default, use the portable hash from phpass
            $wp_hasher = new PasswordHash(8, true);
        }

		$this->db = $wpdb;
		$this->table = $this->db->prefix . 'users_pass';
		$this->users = get_users( array( 'fields' => array( 'ID', 'user_pass' ) ) );
		$this->hasher = $wp_hasher;
		$this->found_pass = false;

		$this->install();
		$this->days_before_update = get_option('tk_days_before_pass_reset');
		$this->hooks();
	}

	public static function get_instance() {
		if( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function install(){
		load_plugin_textdomain( 'tk-force-password', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n' );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}

	function activation(){
		$this->create_database();
		add_option( 'tk_days_before_pass_reset', 30 );
		foreach ( $this->users as $user ) {
			if ( !get_user_meta($user->ID, 'tk_pass_last_updated', true )) {
				add_user_meta( $user->ID, 'tk_pass_last_updated', date("Y-m-d") );				
			}
		}
	}

	function add_settings(){
		register_setting( 'general', 'tk_days_before_pass_reset', 'intval' );
		add_settings_field( 'tk_force_password_days', __( 'Days Before New Password', 'tk-force-password' ), array( $this, 'setting_html_fields' ), 'general' );	
	}

	function setting_html_fields() {
		$value = get_option( 'tk_days_before_pass_reset', '' );
		echo '<input type="text" id="tk_force_password_days" name="tk_days_before_pass_reset" value="' . esc_attr( $value ) . '" />';
	}

	function hooks(){
		add_action( 'wp_login', 				array( $this, 'check_last_update' ), 10, 2);
		add_action( 'template_redirect', 		array( $this, 'redirect' ) );
		add_action( 'current_screen',  			array( $this, 'redirect' ) );
		add_action( 'personal_options_update',  array( $this, 'pass_updated' ) );
		add_action( 'admin_notices', 			array( $this, 'pass_notice' ) );
		add_action( 'admin_init',				array( $this, 'add_settings' ) );
	}

	function create_database() {
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE $this->table (
			id int(20) NOT NULL AUTO_INCREMENT,
			user_id int(20) NOT NULL,
			user_pass varchar(64) NOT NULL,
			created datetime NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		if( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		dbDelta( $sql );
	}

	function get_old_passwords(){
		global $current_user;
		$sql = $this->db->prepare(
			"
			SELECT user_pass
			FROM $this->table
			WHERE user_id = %d
			",
			$current_user->ID );

		$prev_pass = $this->db->get_results( $sql, ARRAY_A );
		if ( !empty( $prev_pass ) ) {
			return $prev_pass;
		}

		return false;
	}

	function insert_new_password_as_old( $password ){
		global $current_user;
		return $this->db->insert( 
			'wp_users_pass', 
			array( 
				'user_id' => $current_user->ID, 
				'user_pass' => $this->hasher->HashPassword( trim( $password ) ),
				'created' => date( 'Y-m-d H:i:s' )
			), 
			array( 
				'%s', 
				'%s',
				'%s'
			) 
		);
	}

	function check_last_update( $user_login, $user ){
		$diff = $this->get_date_diff( $user );
		if ( $diff > $this->days_before_update ) {
			add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 1 );
		}
	}

	private function get_date_diff( $user ){
		$now = time();
		$last_update_str = get_user_meta( $user->ID, 'tk_pass_last_updated', true );
		$last_update_date = strtotime( $last_update_str );
		$datediff = $now - $last_update_date;
		return floor( $datediff / ( 60 * 60 * 24 ) );	
	}

	function login_redirect($redirect_to){
		$redirect_to = admin_url( 'profile.php' );
		return $redirect_to;
	}

	function redirect(){
		global $current_user;

		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'profile' == $screen->base || 'plugins' == $screen->base)
				return;
		}

		if ( ! is_user_logged_in() )
			return;

		if ( $this->get_date_diff( $current_user ) > $this->days_before_update ) {
			wp_redirect( admin_url( 'profile.php' ) );
			exit; // never forget this after wp_redirect!
		}
	}

	function pass_updated( $user_id ) {
		$userObj = get_user_by( 'id', $user_id );

		$pass1 = $pass2 = '';

		if ( isset( $_POST[ 'pass1-text' ] ) )
			$pass1 = $_POST[ 'pass1-text' ];

		if (empty( $pass1 ) || wp_check_password( $pass1, $userObj->data->user_pass, $user_id ) ){
			return;
		}

		$prev_pass = $this->get_old_passwords();
        if ( !empty( $prev_pass ) ) {
	        foreach ( $prev_pass as $pass ) {
	        	if ( wp_check_password( $pass1, $pass['user_pass'], $user_id) ) {
	        		$this->found_pass = true;
	        	}
	        }
	        if ( false == $this->found_pass ) {
				update_user_meta( $user_id, 'tk_pass_last_updated', date( "Y-m-d" ) );
		 	}
        }

	}

	function pass_notice(){
		global $current_user;

		$diff = $this->get_date_diff( $current_user );
		if ( $diff > $this->days_before_update ) {
			echo '<div class="error">';
				echo '<p>' . __( 'Please change your password to be able to keep using this website.', 'tk-force-password' ) . '</p>';
				echo '<p><strong>' . __( 'Remember that you cannot use a password that you used before.', 'tk-force-password' ) . '</strong></p>';
			echo '</div>';
		}
	}

}

if ( !function_exists( 'wp_hash_password' ) ) {
	function wp_hash_password( $password ) {
        global $wp_hasher;
        global $current_user;
        global $wpdb;
        $tk_pass_obj = new TK_Force_New_Password();

        if ( empty( $wp_hasher ) ) {
            require_once( ABSPATH . WPINC . '/class-phpass.php' );
            // By default, use the portable hash from phpass
            $wp_hasher = new PasswordHash( 8, true );
        }

        $prev_pass = $tk_pass_obj->get_old_passwords();
        if ( !empty( $prev_pass ) ) {
	        foreach ( $prev_pass as $pass ) {
	        	if ( wp_check_password( $password, $pass['user_pass'], $current_user->ID) ) {
	        		$tk_pass_obj->found_pass = true;
	        	}
	        }
	        if ( false == $tk_pass_obj->found_pass ) {
	        	$tk_pass_obj->insert_new_password_as_old( $password );
		 	}        	
        }else{
        	$tk_pass_obj->insert_new_password_as_old( $password );
        }

        return $wp_hasher->HashPassword( trim( $password ) );
	}
}

TK_Force_New_Password::get_instance();