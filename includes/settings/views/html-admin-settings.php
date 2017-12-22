<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'aps_settings' );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Advanced Password Security', 'aps' ); ?></h1>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<div class="inside">
						<form method="post" action="admin.php?page=aps">
							<input type="hidden" name="action" value="update">
							<?php wp_nonce_field( 'aps_settings_update' ); ?>
							<table class="form-table">
								<tbody>
									<?php
									$roles = wp_roles();
									$roles = $roles->role_names;
									if ( isset( $roles ) && ! empty( $roles ) ) :
									?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Roles', 'aps' ); ?></th>
											<td>
												<select name="aps_settings[roles][]" multiple>
												<?php foreach ( $roles as $k => $v ) : ?>
													<option value="<?php echo esc_attr( $k ); ?>" <?php selected( in_array( $k, $settings['roles'], true ), true ); ?>><?php echo esc_html( $v ); ?></option>
												<?php endforeach; ?>
												</select>
											</td>
										</tr>
									<?php endif; ?>
									<tr>
										<th scope="row"><?php esc_html_e( 'Reset password in:', 'aps' ); ?></th>
										<td><input type="number" class="text" name="aps_settings[reset_in]" value="<?php echo isset( $settings['reset_in'] ) ? esc_attr( $settings['reset_in'] ) : ''; ?>"></td>
									</tr>
								</tbody>
							</table>
							<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
						</form>

					</div>
				</div>
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<div class="inside">
						<a href="http://trewknowledge.com" target="_blank"><img src="<?php echo plugins_url( '/', APS_PLUGIN_FILE ) . '/assets/images/trewknowledge.svg'; ?>" alt="Trew Knowledge"></a>
						<p><?php esc_html_e( 'Our team of dedicated professionals align themselves with your particular business goals, creating digital development, brand strategy, and customer identity management solutions that take your brand where you want it to be.', 'wp-gigya' ); ?></p>
						<p><a href="http://trewknowledge.com" target="_blank" class="tk_btn"><?php esc_html_e( 'Get in Touch', 'wp-gigya' ); ?></a></p>
					</div>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
