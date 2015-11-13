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

		add_filter( 'pre_update_option_' . APS::$prefix . 'settings', array( $this, 'update_option' ), 10, 2 );
	}

	public function update_option( $new_value, $old_value ) {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['limit'] ) ? $options['limit'] : null;
		
		if ( isset( $options['log_setting_changes'] ) && $old_value !== $new_value ) {
			$users = get_users( array( 'role' => 'administrator', 'fields' => array( 'user_email' ) ) );
			$current_user_name = wp_get_current_user()->display_name;

			$message = sprintf(
				__(
					'
					<h2>This is the old configuration:</h2>
					Day Limit: %d <br>
					Should Save old Passwords: %s <br>
					Should Log Settings Changes: %s <br>
					<br><br>
					<h2>This is the new configuration:</h2>
					Day Limit: %d <br>
					Should Save old Passwords: %s <br>
					Should Log Settings Changes: %s <br>
					',
					APS_TEXTDOMAIN
				),
				$old_value['limit'],
				$old_value['save_old_passwords'] ? __( 'Yes', APS_TEXTDOMAIN ) : __( 'No', APS_TEXTDOMAIN ),
				$old_value['log_setting_changes'] ? __( 'Yes', APS_TEXTDOMAIN ) : __( 'No', APS_TEXTDOMAIN ),
				$new_value['limit'],
				$new_value['save_old_passwords'] ? __( 'Yes', APS_TEXTDOMAIN ) : __( 'No', APS_TEXTDOMAIN ),
				$new_value['log_setting_changes'] ? __( 'Yes', APS_TEXTDOMAIN ) : __( 'No', APS_TEXTDOMAIN )
			);

			$headers = array('Content-Type: text/html; charset=UTF-8');
			$recipients = array();
			foreach ($users as $user) {
				$recipients[] = $user->user_email;
			}
			if( wp_mail( $recipients, sprintf( __('%s changed APS settings on your Wordpress Site', APS_TEXTDOMAIN ), $current_user_name ), $message, $headers ) ) {
				return $new_value;
			}
		}
		return $new_value;
	}

	/**
	 * Check if user can manage options (admin) and display a counter on the admin bar
	 * and a button for reseting all users passwords
	 * @param Object $wp_admin_bar
	 */
	public function admin_bar_counter( $wp_admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			$limit = APS::get_limit();
			$countdown = APS::get_countdown();

			if ( $countdown < $limit / 5 ) {
				$level = 'red';
			} else if ( $countdown < $limit / 2 ) {
				$level = 'yellow';
			} else {
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
				'meta'  => array( 'class' => "aps-counter-$level" ),
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

	/**
	 * Add a settings page under Users menu
	 */
	public function submenu_page() {
		add_users_page(
			esc_html__( 'Advanced Password Security', APS_TEXTDOMAIN ),
			esc_html__( 'Advanced Password Security', APS_TEXTDOMAIN ),
			'manage_options',
			'advanced_password_security',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Renders html markup on the plugin settings page
	 */
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

	/**
	 * Admin init callback function.
	 * It registers all settings, sections and fields to be used on the plugin
	 * settings page.
	 */
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
			APS::$prefix . 'settings_field_log_setting_changes',
			esc_html__( 'Should APS store a log of setting changes?', APS_TEXTDOMAIN ),
			array( $this, 'render_field_log_setting_changes' ),
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

	/**
	 * Renders Settings Section
	 */
	public function render_section() {
		?>
		<p>
			<?php esc_html_e( 'Require users to change their passwords.', APS_TEXTDOMAIN ) ?>
		</p>
		<?php
	}

	/**
	 * Render limit input field
	 */
	public function render_field_limit() {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['limit'] ) ? $options['limit'] : null;
		?>
		<input type="number" min="1" max="365" maxlength="3" name="<?php printf( '%ssettings[limit]', esc_attr( APS::$prefix ) ) ?>" placeholder="<?php echo esc_attr( '30' ) ?>" value="<?php echo esc_attr( $value ) ?>">
		<?php
		esc_html_e( 'days', APS_TEXTDOMAIN );
	}

	/**
	 * Render should save old password checkbox
	 */
	public function render_field_save_old_passwords() {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['save_old_passwords'] ) ? $options['save_old_passwords'] : null;
		?>
		<input type="checkbox" name="<?php printf( '%ssettings[save_old_passwords]', esc_attr( APS::$prefix ) ) ?>" <?php echo $value ? 'checked' : '' ?>>
		<?php
	}

	/**
	 * Render should log all setting changes
	 */
	public function render_field_log_setting_changes() {
		$options = get_option( APS::$prefix . 'settings' );
		$value   = isset( $options['log_setting_changes'] ) ? $options['log_setting_changes'] : null;
		?>
		<input type="checkbox" name="<?php printf( '%ssettings[log_setting_changes]', esc_attr( APS::$prefix ) ) ?>" <?php echo $value ? 'checked' : '' ?>>
		<?php
	}

	/**
	 * Render reset all button
	 */
	public function render_field_reset_all_users() {
		?>
		<input type="button" value="<?php esc_attr_e( strtoupper( 'Reset all passwords' ), APS_TEXTDOMAIN ); ?>" id="reset_all_users_settings_button" class="button">
		<?php
	}
}
