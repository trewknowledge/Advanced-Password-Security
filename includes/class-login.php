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

}
