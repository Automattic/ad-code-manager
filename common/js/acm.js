(function($) {
inlineEditAdCodes = {

	init : function() {
		var t = this, row = $('#inline-edit');

		t.what = '#ad-code-';

		$('.acm-ajax-edit').live('click', function(){
			inlineEditAdCodes.edit(this);
			jQuery('.add-more-conditionals').off( 'click.acm_add_more_conditionals', acm_add_more_conditionals );
			jQuery('.add-more-conditionals').on( 'click.acm_add_more_conditionals', acm_add_more_conditionals );
			jQuery('.acm-remove-conditional').on( 'click.acm_remove_conditional', acm_remove_conditional );
			return false;
		});

		// prepare the edit row
		row.keyup(function(e) { if(e.which == 27) return inlineEditAdCodes.revert(); });

		$('a.cancel', row).click(function() { return inlineEditAdCodes.revert(); });
		$('a.save', row).click(function() { return inlineEditAdCodes.save(this); });
		$('input, select', row).keydown(function(e) { if(e.which == 13) return inlineEditAdCodes.save(this); });

		$('#posts-filter input[type="submit"]').mousedown(function(e){
			t.revert();
		});
	},

	toggle : function(el) {
		var t = this;
		$(t.what+t.getId(el)).css('display') == 'none' ? t.revert() : t.edit(el);
	},

	edit : function(id) {
		var t = this, editRow;
		t.revert();

		if ( typeof(id) == 'object' )
			id = t.getId(id);

		editRow = $('#inline-edit').clone(true), rowData = $('#inline_'+id);
		$('td', editRow).attr('colspan', $('.widefat:first thead th:visible').length);

		if ( $(t.what+id).hasClass('alternate') )
			$(editRow).addClass('alternate');

		$(t.what+id).hide().after(editRow);

		$('input[name="id"]', editRow).val( $('.id', rowData).text() );
		$('.acm-conditional-fields', editRow).html( $('.acm-conditional-fields', rowData).html() );
		$('.acm-column-fields', editRow).html( $('.acm-column-fields', rowData).html() );
		$('.acm-priority-field', editRow).html( $('.acm-priority-field', rowData).html() );
		$('.acm-operator-field', editRow).html( $('.acm-operator-field', rowData).html() );

		$(editRow).attr('id', 'edit-'+id).addClass('inline-editor').show();
		$('.ptitle', editRow).eq(0).focus();

		return false;
	},

	save : function(id) {

		if( typeof(id) == 'object' )
			id = this.getId(id);

		$('table.widefat .inline-edit-save .waiting').show();
		// Get all of our field parameters
		inline_edit = $('#edit-'+id ).find('fieldset').wrap('<form action="POST" ></form>');
		params = inline_edit.closest('form').serializeArray();

		// make ajax request
		$.post(ajaxurl, params,
			function(r) {
				var row, new_id;
				$('table.widefat .inline-edit-save .waiting').hide();

				if (r) {
					if ( -1 != r.indexOf('<tr') ) {
						$(inlineEditAdCodes.what+id).remove();
						new_id = $(r).attr('id');

						$('#edit-'+id).before(r).remove();
						row = new_id ? $('#'+new_id) : $(inlineEditAdCodes.what+id);
						row.hide().fadeIn();
					} else
						$('#edit-'+id+' .inline-edit-save .error').html(r).show();
				} else
					$('#edit-'+id+' .inline-edit-save .error').html(inlineEditL10n.error).show();
			}
		);
		return false;
	},

	revert : function() {
		var id = $('table.widefat tr.inline-editor').attr('id');

		if ( id ) {
			$('table.widefat .inline-edit-save .waiting').hide();
			$('#'+id).remove();
			id = id.substr( id.lastIndexOf('-') + 1 );
			$(this.what+id).show();
		}

		return false;
	},

	getId : function(o) {
		var id = o.tagName == 'TR' ? o.id : $(o).parents('tr').attr('id'), parts = id.split('-');
		return parts[parts.length - 1];
	}
};

$(document).ready(function(){inlineEditAdCodes.init();});
})(jQuery);

var acm_add_more_conditionals = function() {
	var temp = jQuery( 'div#conditional-single-field-master').clone( false );
	temp.removeAttr('id');
	jQuery(temp).find('.conditional-arguments').append( '<a href="#" class="acm-remove-conditional">Remove</a>' );
	jQuery(this).closest('.acm-conditional-fields').find('.form-new-row').append(temp);
	jQuery('.acm-remove-conditional').off( 'click.acm_remove_conditional', acm_remove_conditional );
	jQuery('.acm-remove-conditional').on( 'click.acm_remove_conditional', acm_remove_conditional );
	return false;
}

var acm_remove_conditional = function() {
	jQuery(this).closest('.conditional-single-field').remove();
	return false;
}

jQuery( document ).ready( function( $ ) {

	jQuery('.add-more-conditionals').on( 'click.acm_add_more_conditionals', acm_add_more_conditionals );
	$('#conditionals-help-toggler').click( function( e ) {
		var el = jQuery('#conditionals-help');

		el.toggleClass('hidden');
	});
});