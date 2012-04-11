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
} );