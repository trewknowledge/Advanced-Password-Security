<?php

namespace Advanced_Password_Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Advanced_Password_Security as APS;

class Settings {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'submenu_page' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_counter' ), 999 );
	}

	public function admin_bar_counter( $wp_admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			$limit = APS::get_limit();
			$countdown = APS::get_countdown();

			if ( $countdown < $limit / 5 ) {
				$level = 'red';
			}else if ( $countdown < $limit / 2 ) {
				$level = 'yellow';
			}else{
				$level = 'green';
			}

			$args = array(
				'id'    => 'aps_counter',
				'title' => sprintf(
					_n(
						'%d day before password reset',
						'%d days before password reset',
						$countdown,
						APS_TEXTDOMAIN
					),
					$countdown
				),
				'parent' => 'top-secondary',
				'meta'  => array( "class" => "aps-counter-$level" )
			);
			$wp_admin_bar->add_node( $args );

			$args = array(
				'id'    => 'aps_reset_all',
				'title' => __( 'Reset all passwords', APS_TEXTDOMAIN ),
				'parent' => 'aps_counter',
				'href' => '#',
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function submenu_page() {
		add_users_page(
			esc_html__( 'Advanced Password Security', APS_TEXTDOMAIN ),
			esc_html__( 'Advanced Password Security', APS_TEXTDOMAIN ),
			'manage_options',
			'advanced_password_security',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Advanced Password Security', APS_TEXTDOMAIN ) ?></h2>

			<form method="post" action="options.php">

				<?php settings_fields( APS::$prefix . 'settings_page' ); ?>

				<?php do_settings_sections( APS::$prefix . 'settings_page' ); ?>

				<?php submit_button(); ?>

			</form>

		</div>
		<?php
	}

	public function init() {
		register_setting(
			APS::$prefix . 'settings_page',
			APS::$prefix . 'settings'
		);

		add_settings_section(
			APS::$prefix . 'settings_page_section',
			null,
			array( $this, 'render_section' ),
			APS::$prefix . 'settings_page'
		);

		add_settings_field(
			APS::$prefix . 'settings_field_limit',
			esc_html__( 'Require a new password every', APS_TEXTDOMAIN ),
			array( $this, 'render_field_limit' ),
			APS::$prefix . 'settings_page',
			APS::$prefix . 'settings_page_section'
		);

		add_settings_field(
			APS::$prefix . 'settings_field_save_old_passwords',
			esc_html__( 'Prevent users from using previously used passwords', APS_TEXTDOMAIN ),
			array( $this, 'render_field_save_old_passwords' ),
			APS::$prefix . 'settings_page',
			APS::$prefix . 'settings_page_section'
		);
		add_settings_field(
			APS::$prefix . 'settings_field_reset_all_users',
			esc_html__( 'Reset all users password', APS_TEXTDOMAIN ),
			array( $this, 'render_field_reset_all_users' ),
			APS::$prefix . 'settings_page',
			APS::$prefix . 'settings_page_section'
		);
	}

	public function render_section() {
		?>
		<p>
			<?php esc_html_e( 'Require users to change their passwords.', APS_TEXTDOMAIN ) ?>
		</p>
		<?php
	}

	public function render_field_limit() {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['limit'] ) ? $options['limit'] : null;
		?>
		<input type="number" min="1" max="365" maxlength="3" name="<?php printf( '%ssettings[limit]', APS::$prefix ) ?>" placeholder="<?php echo esc_attr( '30' ) ?>" value="<?php echo esc_attr( $value ) ?>">
		<?php
		esc_html_e( 'days', APS_TEXTDOMAIN );
	}

	public function render_field_save_old_passwords() {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['save_old_passwords'] ) ? $options['save_old_passwords'] : null;
		?>
		<input type="checkbox" name="<?php printf( '%ssettings[save_old_passwords]', APS::$prefix ) ?>" <?php echo $value ? 'checked' : '' ?>>
		<?php
	}

	public function render_field_reset_all_users() {
		?>
		<input type="button" value="<?php _e( strtoupper( 'Reset all passwords' ), APS_TEXTDOMAIN ); ?>" id="reset_all_users_settings_button" class="button">
		<?php
	}

}
