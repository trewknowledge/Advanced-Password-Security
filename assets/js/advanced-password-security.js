( function( $ ){
	'use strict';

	$( '#wp-admin-bar-aps_reset_all a, #reset_all_users_settings_button' ).on( 'click', function(e){
		e.preventDefault();

		$.ajax({
            url: APS_Ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'reset-all-passwords',
                ticket: APS_Ajax.ajaxnonce
            },
            success: function(response){
                window.location = APS_Ajax.loginurl
            }
        });

	} );

} )( jQuery );