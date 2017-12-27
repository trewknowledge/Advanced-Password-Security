<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APS {

	private $users;
	private static $pwd;
	private static $hashed_pass;


	private static $instance;

	function __construct() {
		self::includes();
		global $wpdb;

		$this->users      = get_users( array( 'fields' => array( 'ID', 'user_pass' ) ) );
		$this->found_pass = false;

		$this->install();
		$this->settings = get_option( 'aps_settings' );
		$this->hooks();
	}

	public static function set_password( $password ) {
		if ( is_null( self::$pwd ) ) {
			self::$pwd = $password;
		}
	}

	public static function set_hash( $hash ) {
		if ( is_null( self::$hashed_pass ) ) {
			self::$hashed_pass = $hash;
		}
	}

	private static function includes() {
		include_once dirname( __FILE__ ) . '/settings/class-aps-settings.php';
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function install() {
		load_plugin_textdomain( 'aps', false, plugin_dir_path( APS_PLUGIN_FILE ) . 'i18n' );
		register_activation_hook( APS_PLUGIN_FILE, array( $this, 'activation' ) );
	}

	function activation() {
		add_option(
			'aps_settings', array(
				'reset_in' => 30,
				'roles'    => array( 'administrator', 'editor', 'author', 'contributor' ),
			)
		);
		foreach ( $this->users as $user ) {
			if ( ! get_user_meta( $user->ID, 'aps_last_updated', true ) ) {
				add_user_meta( $user->ID, 'aps_last_updated', date( 'Y-m-d H:i:s' ) );
			}
			add_user_meta( $user->ID, 'aps_used_passwords', $user->user_pass );
		}
	}


	private function hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( APS_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

		add_action( 'wp_login', array( $this, 'check_last_update' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'redirect' ) );
		add_action( 'current_screen', array( $this, 'redirect' ) );

		add_action( 'profile_update', array( $this, 'profile_update' ) );
		add_action( 'user_register', array( $this, 'profile_update' ) );

		add_action( 'admin_notices', array( $this, 'pass_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'woocommerce_save_account_details_errors', array( $this, 'woo_save_account' ), 10, 2 );
	}

	function plugin_action_links( $links ) {
		$setting_link = admin_url( 'admin.php?page=aps');

		$plugin_links = array(
			'<a href="' . $setting_link . '">' . esc_html__( 'Settings', 'aps' ) . '</a>',
			'<a href="https://github.com/trewknowledge/Advanced-Password-Security/issues" target="_blank">' . __( 'Support', 'aps' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	function load_assets() {
		wp_enqueue_style( 'aps-css', plugins_url( '/', APS_PLUGIN_FILE ) . '/assets/css/aps.css' );
	}

	function woo_save_account( $errors, $user ) {
		$is_new_password = $this->is_new_password( $user->user_pass, $user->ID );
		if ( ! $is_new_password ) {
			$errors->add( 'woocommerce_password_error', esc_html__( 'Password must not have been used before.', 'aps' ) );
		}

		return $errors;
	}

	function profile_update( $user_id ) {
		if ( ! isset( $this->settings ) || ! $this->settings ) {
			return;
		}

		$is_new_password = $this->is_new_password( self::$pwd, $user_id );
		if ( $is_new_password ) {
			$user = get_userdata( $user_id );
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $this->settings['roles'], true ) ) {
					add_user_meta( $user_id, 'aps_used_passwords', self::$hashed_pass );
					update_user_meta( $user_id, 'aps_last_updated', date( 'Y-m-d H:i:s' ) );
					break;
				}
			}
		}
	}

	function is_new_password( $password, $user_id ) {
		$prev_pass = $this->get_old_passwords( $user_id );
		if ( empty( $prev_pass ) ) {
			return true;
		}

		foreach ( $prev_pass as $pass ) {
			if ( wp_check_password( trim( $password ), $pass, $user_id ) ) {
				return false;
			}
		}

		return true;
	}

	function get_old_passwords( $user_id ) {
		$prev_pass = get_user_meta( $user_id, 'aps_used_passwords', false );
		if ( ! empty( $prev_pass ) ) {
			return $prev_pass;
		}

		return false;
	}

	function check_last_update( $user_login, $user ) {
		if ( ! isset( $this->settings ) || ! $this->settings ) {
			return;
		}

		$diff = $this->get_date_diff( $user->ID );

		if ( $diff > $this->settings['reset_in'] ) {
			add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 1 );
		}
	}

	private function get_date_diff( $user_id ) {
		$now              = time();
		$last_update_str  = get_user_meta( $user_id, 'aps_last_updated', true );
		$last_update_date = strtotime( $last_update_str );
		$datediff         = $now - $last_update_date;
		return floor( $datediff / ( 60 * 60 * 24 ) );
	}

	function login_redirect( $redirect_to ) {
		$redirect_to = admin_url( 'profile.php' );
		return $redirect_to;
	}

	function redirect() {
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( 'profile' === $screen->base ) {
				return;
			}
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! isset( $this->settings ) || ! $this->settings ) {
			return;
		}

		$user = wp_get_current_user();

		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $this->settings['roles'], true ) ) {
				if ( $this->get_date_diff( $user->ID ) > $this->settings['reset_in'] ) {
					wp_safe_redirect( admin_url( 'profile.php' ) );
					exit;
				}
			}
		}

	}

	function pass_notice() {
		if ( ! isset( $this->settings ) || ! $this->settings ) {
			return;
		}

		$user_id = get_current_user_id();

		$diff = $this->get_date_diff( $user_id );
		if ( $diff > $this->settings['reset_in'] ) {
			echo '<div class="error">';
				echo '<p>' . esc_html__( 'Please change your password to be able to keep using this website.', 'tk-force-password' ) . '</p>';
				echo '<p><strong>' . esc_html__( 'Remember that you cannot use a password that you used before.', 'tk-force-password' ) . '</strong></p>';
			echo '</div>';
		}
	}

}

if ( ! function_exists( 'wp_hash_password' ) ) {
	function wp_hash_password( $password ) {
		global $wp_hasher;
		if ( empty( $wp_hasher ) ) {
			require_once( ABSPATH . WPINC . '/class-phpass.php' );
			// By default, use the portable hash from phpass
			$wp_hasher = new PasswordHash( 8, true );
		}
		$hashed_pass = $wp_hasher->HashPassword( trim( $password ) );

		APS::set_password( $password );
		APS::set_hash( $hashed_pass );

		return $hashed_pass;
	}
}

APS::get_instance();
