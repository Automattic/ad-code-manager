/**
 * Conditional Autocomplete for Ad Code Manager
 *
 * Provides Select2-powered autocomplete for conditional arguments
 * like categories, tags, pages, etc.
 *
 * @since 0.9.0
 */
( function( $, acmAutocomplete ) {
	'use strict';

	if ( typeof acmAutocomplete === 'undefined' ) {
		return;
	}

	var ConditionalAutocomplete = {

		/**
		 * Initialize the autocomplete functionality.
		 */
		init: function() {
			this.bindEvents();
			this.initExistingFields();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Use event delegation for dynamically added conditional selects.
			$( document ).on( 'change', 'select[name="acm-conditionals[]"]', function() {
				self.handleConditionalChange( $( this ) );
			});

			// Re-initialize when new conditional rows are added.
			$( document ).on( 'click', '.add-more-conditionals', function() {
				// Small delay to allow DOM to update.
				setTimeout( function() {
					self.initExistingFields();
				}, 100 );
			});
		},

		/**
		 * Initialize any existing conditional fields on page load.
		 */
		initExistingFields: function() {
			var self = this;

			$( 'select[name="acm-conditionals[]"]' ).each( function() {
				var $select = $( this );
				var conditional = $select.val();

				if ( conditional && self.hasAutocomplete( conditional ) ) {
					self.initAutocomplete( $select );
				}
			});
		},

		/**
		 * Handle when a conditional select changes.
		 *
		 * @param {jQuery} $select The conditional select element.
		 */
		handleConditionalChange: function( $select ) {
			var conditional = $select.val();
			var $argumentsContainer = $select.closest( '.conditional-single-field' ).find( '.conditional-arguments' );
			var $input = $argumentsContainer.find( 'input[name="acm-arguments[]"]' );

			// Destroy existing Select2 if present.
			if ( $input.hasClass( 'select2-hidden-accessible' ) ) {
				$input.select2( 'destroy' );
			}

			// Reset the input.
			$input.val( '' ).attr( 'type', 'text' ).show();

			// If this conditional supports autocomplete, initialize it.
			if ( conditional && this.hasAutocomplete( conditional ) ) {
				this.initAutocomplete( $select );
			}
		},

		/**
		 * Check if a conditional has autocomplete configuration.
		 *
		 * @param {string} conditional The conditional function name.
		 * @return {boolean} True if autocomplete is available.
		 */
		hasAutocomplete: function( conditional ) {
			return acmAutocomplete.conditionals.hasOwnProperty( conditional );
		},

		/**
		 * Get the autocomplete configuration for a conditional.
		 *
		 * @param {string} conditional The conditional function name.
		 * @return {Object|null} The configuration or null.
		 */
		getConfig: function( conditional ) {
			return acmAutocomplete.conditionals[ conditional ] || null;
		},

		/**
		 * Initialize Select2 autocomplete on an arguments input.
		 *
		 * @param {jQuery} $conditionalSelect The conditional select element.
		 */
		initAutocomplete: function( $conditionalSelect ) {
			var self = this;
			var conditional = $conditionalSelect.val();
			var config = this.getConfig( conditional );

			if ( ! config ) {
				return;
			}

			var $argumentsContainer = $conditionalSelect.closest( '.conditional-single-field' ).find( '.conditional-arguments' );
			var $input = $argumentsContainer.find( 'input[name="acm-arguments[]"]' );
			var currentValue = $input.val();

			// Initialize Select2 with AJAX.
			$input.select2({
				ajax: {
					url: acmAutocomplete.ajaxUrl,
					dataType: 'json',
					delay: 250,
					data: function( params ) {
						return {
							action: 'acm_search_terms',
							nonce: acmAutocomplete.nonce,
							search: params.term,
							conditional: conditional,
							type: config.type,
							taxonomy: config.taxonomy || '',
							post_type: config.post_type || ''
						};
					},
					processResults: function( response ) {
						if ( response.success && response.data.results ) {
							return {
								results: response.data.results
							};
						}
						return { results: [] };
					},
					cache: true
				},
				minimumInputLength: acmAutocomplete.minChars,
				placeholder: self.getPlaceholder( conditional ),
				allowClear: true,
				tags: true, // Allow custom values (user can type their own).
				createTag: function( params ) {
					var term = $.trim( params.term );
					if ( term === '' ) {
						return null;
					}
					return {
						id: term,
						text: term,
						newTag: true
					};
				},
				language: {
					inputTooShort: function() {
						return acmAutocomplete.i18n.inputTooShort;
					},
					searching: function() {
						return acmAutocomplete.i18n.searching;
					},
					noResults: function() {
						return acmAutocomplete.i18n.noResults;
					},
					errorLoading: function() {
						return acmAutocomplete.i18n.errorLoading;
					}
				},
				width: '100%'
			});

			// If there's an existing value, set it.
			if ( currentValue ) {
				var option = new Option( currentValue, currentValue, true, true );
				$input.append( option ).trigger( 'change' );
			}
		},

		/**
		 * Get placeholder text for a conditional.
		 *
		 * @param {string} conditional The conditional function name.
		 * @return {string} The placeholder text.
		 */
		getPlaceholder: function( conditional ) {
			var placeholders = {
				'is_category': acmAutocomplete.i18n.searchCategories || 'Search categories...',
				'has_category': acmAutocomplete.i18n.searchCategories || 'Search categories...',
				'is_tag': acmAutocomplete.i18n.searchTags || 'Search tags...',
				'has_tag': acmAutocomplete.i18n.searchTags || 'Search tags...',
				'is_page': acmAutocomplete.i18n.searchPages || 'Search pages...',
				'is_single': acmAutocomplete.i18n.searchPosts || 'Search posts...'
			};

			return placeholders[ conditional ] || 'Search...';
		}
	};

	// Initialize when document is ready.
	$( document ).ready( function() {
		ConditionalAutocomplete.init();
	});

} )( jQuery, window.acmAutocomplete );
