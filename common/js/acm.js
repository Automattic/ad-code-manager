jQuery( document ).ready( function( $ ) {
    var conditional_count = 1;
	$('#add-more-conditionals').click(function() {
        jQuery('#conditional-tpl').append( '<div class="form-new-row">' +
            '<select name="conditionals[' + conditional_count + '][function]" id="acm-conditional-' + conditional_count + '"></select></div>' +
            '<div class="form-half">' +
            '<input name="conditionals[' + conditional_count + '][arguments]" id="acm-argument-' + conditional_count + '" type="text" value="" size="20"></div>' );
        jQuery('#acm-conditional-0 option').clone().appendTo('#acm-conditional-' + conditional_count );
        conditional_count++;
	});
	jQuery('#conditionals-help-toggler').click( function( e ) {
		var el = jQuery('#conditionals-help');
		
		el.toggleClass('hidden');
	});
    jQuery('.ad-code-edit').click( function( $ ) {
        var url_request = ajaxurl + jQuery(this).val();
        var data = {
            action: 'acm_edit_ad_code',
            data: post_id,
            nonce: $( '#acm_edit_ad_code_nonce' ).val()
        };
        $.post( ajaxurl, data, function( response ) {
            $('.edit_acm_view').replaceWith( response );
        } );
    });
    jQuery('.acm-ajax-edit').click( function($) {
        var post_id = parseInt( jQuery(this).attr('id').split('-')[1] );
        jQuery('.acm-edit-display').hide();
        jQuery('.acm-record-display').show();
        jQuery('#record_' + post_id ).hide();
        jQuery('#acm-conditional-0 option').clone().appendTo('.cond_' + post_id );
        jQuery('#record_display_' + post_id ).show();
        jQuery('#acm-cancel-edit-' + post_id ).click( function($) {
            jQuery('.acm-edit-display').hide();
            jQuery('.acm-record-display').show();
        });
	    if ( 1 == jQuery('.acm-edit-cond', '#record_display_' + post_id ).length ) {
		    jQuery('.acm-x-cond', '#record_display_' + post_id ).remove();
	    } else {
		    jQuery('.acm-x-cond').click( function($) {
			    var record_id = parseInt( jQuery(this).attr('id').split('-')[1] );
			    jQuery('#acm-edit-cond-' + record_id ).remove();
			    if ( 1 == jQuery('.acm-edit-cond', '#record_display_' + post_id ).length ) {
				    jQuery('.acm-x-cond', '#record_display_' + post_id ).remove();
			    }
		    });
	    }
    });
	jQuery('.acm-ajax-delete').click( function($) {
		var $this = jQuery(this);
        var post_id = parseInt( $this.attr('id').split('-')[1] );
		var url = '/?acm-request=true&acm-action=delete&acm-id=' + post_id;
		var data = { 'acm-nonce': acm_ajax_nonce }
		jQuery.post( url , data , function( response ) {
			if (1 == response) {
				$this.parents('tr').hide('fast');
			}
		} );
		
	});

});