<?php 

namespace Advanced_Password_Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Advanced_Password_Security as APS;

class Login {

	
	public function __construct(){
		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );
		add_action( 'validate_password_reset', array( $this, 'validate_password' ), 10, 2 );
		add_filter( 'login_message', array( $this, 'lost_password_message' ) );
	}

	public function login( $user_login, $user ) {
		if ( !APS::is_password_expired( $user ) ) {
			return;
		}

		wp_destroy_all_sessions();

		$redirect = add_query_arg(
			array(
				'action' => 'lostpassword',
			),
			wp_login_url()
		);
		wp_safe_redirect( $redirect, 302 );
		exit;
	}

	public function validate_password( $errors, $user ) {
		$new_pass = isset( $_POST['pass1'] ) && $_POST['pass1'] ? $_POST['pass1'] : '';
		$new_pass2 = isset( $_POST['pass2'] ) && $_POST['pass2'] ? $_POST['pass2'] : '';

		if ( !empty($new_pass) && $new_pass === $new_pass2 ) {
			$same_pass = wp_check_password( $new_pass, $user->data->user_pass, $user->ID );
			if ( $same_pass ) {
				$errors->add( 'password_already_used', __( 'You cannot reuse your old password.', APS_TEXTDOMAIN ) );
				return;
			}

			$used_passwords = APS::get_old_passwords( $user );

			foreach ($used_passwords as $old_pass) {
				if ( wp_check_password( $new_pass, $old_pass['pass'], $user->ID ) ) {
					$errors->add( 'password_already_used', __( 'You cannot reuse a password that you used in the past.', APS_TEXTDOMAIN ) );
					$errors->add( 'password_already_used', sprintf( __( 'You used this password back in %s.', APS_TEXTDOMAIN ), $old_pass['created'] ) );
					return;
				}
			}

			global $wpdb;
			$hash = wp_hash_password( $new_pass );
			$created = date('F jS, Y');
			array_push( $used_passwords, array( 'pass' => $hash, 'created' => $created ) );
			$used_passwords = maybe_serialize( $used_passwords );

		    $wpdb->update( $wpdb->users, array( 'old_user_pass' => $used_passwords ), array( 'ID' => $user->ID ) );
		 
			update_user_meta( $user->ID, APS::META_KEY, gmdate( "U" ) );			
		}
	}

}
