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
		 * Check if Select2 is available.
		 *
		 * @return {boolean} True if Select2 is loaded.
		 */
		isSelect2Available: function() {
			return typeof $.fn.select2 === 'function';
		},

		/**
		 * Initialize the autocomplete functionality.
		 */
		init: function() {
			this.bindEvents();
			this.initExistingFields();
			this.updateAddButtonVisibility();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Use event delegation for dynamically added conditional selects (Add and Edit forms).
			$( document ).on( 'change', '#add-adcode select[name="acm-conditionals[]"], #edit-adcode select[name="acm-conditionals[]"]', function() {
				self.handleConditionalChange( $( this ) );
				self.updateAddButtonVisibility();
				self.updateOperatorVisibility();
			});

			// Handle adding new conditional rows.
			$( document ).on( 'click', '.add-more-conditionals', function( e ) {
				e.preventDefault();
				var $button = $( this );
				var $form = $button.closest( 'form' );
				var $container = $form.find( '.form-new-row' );

				self.addConditionalRow( $container );

				// Small delay to allow DOM to update, then clean up.
				setTimeout( function() {
					self.cleanupNewRows();
					self.updateAddButtonVisibility();
					self.updateOperatorVisibility();
				}, 100 );
			});

			// Handle remove conditional click.
			$( document ).on( 'click', '.acm-remove-conditional', function( e ) {
				e.preventDefault();
				var $row = $( this ).closest( '.conditional-single-field' );
				$row.remove();

				setTimeout( function() {
					self.updateAddButtonVisibility();
					self.updateOperatorVisibility();
				}, 100 );
			});
		},

		/**
		 * Initialize any existing conditional fields on page load.
		 * Targets both Add and Edit forms.
		 */
		initExistingFields: function() {
			var self = this;

			// Target both Add and Edit forms.
			$( '#add-adcode select[name="acm-conditionals[]"], #edit-adcode select[name="acm-conditionals[]"]' ).each( function() {
				var $select = $( this );
				var conditional = $select.val();
				var $argumentsContainer = $select.closest( '.conditional-single-field' ).find( '.conditional-arguments' );

				// Hide arguments for empty selection or no-parameter conditionals.
				if ( ! conditional || self.hasNoParameters( conditional ) ) {
					$argumentsContainer.hide();
					return;
				}

				// Show arguments and init autocomplete if applicable.
				$argumentsContainer.show();

				// Only init autocomplete if not already initialized.
				var $existingSelect2 = $argumentsContainer.find( 'select.acm-autocomplete-select' );
				if ( self.hasAutocomplete( conditional ) && ! $existingSelect2.length ) {
					self.initAutocomplete( $select );
				}
			});
		},

		/**
		 * Add a new conditional row to the form.
		 *
		 * @param {jQuery} $container The container to add the row to.
		 */
		addConditionalRow: function( $container ) {
			var $master = $container.find( '#conditional-single-field-master' );

			if ( ! $master.length ) {
				$master = $container.find( '.conditional-single-field' ).first();
			}

			if ( ! $master.length ) {
				return;
			}

			// Clone the master row.
			var $newRow = $master.clone();

			// Remove the master ID so only one exists.
			$newRow.removeAttr( 'id' );

			// Reset select to default.
			$newRow.find( 'select[name="acm-conditionals[]"]' ).val( '' );

			// Reset and show the input.
			var $input = $newRow.find( 'input[name="acm-arguments[]"]' );
			$input.val( '' ).show();

			// Remove any Select2 elements that may have been cloned.
			$newRow.find( 'select.acm-autocomplete-select' ).remove();
			$newRow.find( '.select2-container' ).remove();

			// Restore hidden input if present.
			var $hiddenInput = $newRow.find( 'input[data-original-name="acm-arguments[]"]' );
			if ( $hiddenInput.length ) {
				$hiddenInput
					.attr( 'name', 'acm-arguments[]' )
					.removeAttr( 'data-original-name' )
					.val( '' )
					.show();
			}

			// Add remove link if not present.
			var $arguments = $newRow.find( '.conditional-arguments' );
			if ( ! $arguments.find( '.acm-remove-conditional' ).length ) {
				$arguments.append( ' <a href="#" class="acm-remove-conditional">Remove</a>' );
			}

			// Hide the arguments container initially.
			$arguments.hide();

			// Append to container.
			$container.append( $newRow );
		},

		/**
		 * Clean up newly added conditional rows.
		 *
		 * When rows are cloned from the master template, they may contain
		 * leftover Select2 markup that needs to be cleaned up.
		 */
		cleanupNewRows: function() {
			var self = this;

			$( 'select[name="acm-conditionals[]"]' ).each( function() {
				var $conditionalSelect = $( this );
				var conditional = $conditionalSelect.val();
				var $argumentsContainer = $conditionalSelect.closest( '.conditional-single-field' ).find( '.conditional-arguments' );

				// If no conditional is selected, clean up any Select2 remnants.
				if ( ! conditional ) {
					// Remove any cloned Select2 elements.
					$argumentsContainer.find( 'select.acm-autocomplete-select' ).remove();
					$argumentsContainer.find( '.select2-container' ).remove();

					// Restore the original input if it was hidden.
					var $hiddenInput = $argumentsContainer.find( 'input[data-original-name="acm-arguments[]"]' );
					if ( $hiddenInput.length ) {
						$hiddenInput
							.attr( 'name', 'acm-arguments[]' )
							.removeAttr( 'data-original-name' )
							.val( '' )
							.show();
					}

					// Ensure input exists and is visible with correct attributes.
					var $input = $argumentsContainer.find( 'input[name="acm-arguments[]"]' );
					if ( ! $input.length ) {
						$argumentsContainer.prepend( '<input name="acm-arguments[]" type="text" value="" size="20" />' );
					} else {
						$input.val( '' ).attr( 'type', 'text' ).show();
					}

					// Hide the arguments container since no conditional is selected.
					$argumentsContainer.hide();
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

			// Check if we have a Select2 select element or the original input.
			var $existingSelect = $argumentsContainer.find( 'select.acm-autocomplete-select' );
			var $hiddenInput = $argumentsContainer.find( 'input[data-original-name="acm-arguments[]"]' );

			// If there's a Select2 select, destroy it and restore the input.
			if ( $existingSelect.length ) {
				var currentVal = $existingSelect.val();
				$existingSelect.select2( 'destroy' );
				$existingSelect.remove();

				// Restore the original input's name and visibility.
				$hiddenInput
					.attr( 'name', 'acm-arguments[]' )
					.removeAttr( 'data-original-name' )
					.val( currentVal || '' )
					.show();
			}

			// Get the input (either restored or original).
			var $input = $argumentsContainer.find( 'input[name="acm-arguments[]"]' );

			// Reset the input value.
			$input.val( '' );

			// Hide arguments container when no conditional selected or for conditionals that take no parameters.
			if ( ! conditional || this.hasNoParameters( conditional ) ) {
				$argumentsContainer.hide();
				return;
			}

			// Show arguments container for conditionals that take parameters.
			$argumentsContainer.show();

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

			// Skip if Select2 is not available (CDN failed to load).
			if ( ! this.isSelect2Available() ) {
				return;
			}

			var $argumentsContainer = $conditionalSelect.closest( '.conditional-single-field' ).find( '.conditional-arguments' );
			var $input = $argumentsContainer.find( 'input[name="acm-arguments[]"]' );
			var currentValue = $input.val();

			// Hide the original input.
			$input.hide();

			// Create a select element for Select2 (it requires a <select> for AJAX).
			var $select = $( '<select></select>' )
				.attr( 'name', 'acm-arguments[]' )
				.addClass( 'acm-autocomplete-select' );

			// If there's an existing value, add it as an option.
			if ( currentValue ) {
				$select.append( new Option( currentValue, currentValue, true, true ) );
			}

			// Insert the select after the hidden input.
			$input.after( $select );

			// Remove the name from the original input to avoid duplicate submission.
			$input.removeAttr( 'name' ).attr( 'data-original-name', 'acm-arguments[]' );

			// Determine the dropdown parent - use body for inline edit to avoid z-index issues.
			var $dropdownParent = $argumentsContainer.closest( '.acm-editor-row' ).length
				? $( 'body' )
				: $argumentsContainer;

			// Initialize Select2 with AJAX.
			$select.select2({
				dropdownParent: $dropdownParent,
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
		},

		/**
		 * Check if a conditional takes no parameters.
		 *
		 * @param {string} conditional The conditional function name.
		 * @return {boolean} True if the conditional takes no parameters.
		 */
		hasNoParameters: function( conditional ) {
			var noParamConditionals = [
				'is_home',
				'is_front_page',
				'is_archive',
				'is_search',
				'is_404',
				'is_date',
				'is_year',
				'is_month',
				'is_day',
				'is_time',
				'is_feed',
				'is_comment_feed',
				'is_trackback',
				'is_preview',
				'is_paged',
				'is_admin'
			];

			return noParamConditionals.indexOf( conditional ) !== -1;
		},

		/**
		 * Update visibility of the "Add another condition" button.
		 *
		 * Shows the button only when at least one condition is selected.
		 */
		updateAddButtonVisibility: function() {
			var $forms = $( '#add-adcode, #edit-adcode' );

			$forms.each( function() {
				var $form = $( this );
				var $addButton = $form.find( '.form-add-more' );
				var hasSelectedCondition = false;

				// Check if any conditional select has a value.
				$form.find( 'select[name="acm-conditionals[]"]' ).each( function() {
					if ( $( this ).val() ) {
						hasSelectedCondition = true;
						return false; // Break the loop.
					}
				});

				if ( hasSelectedCondition ) {
					$addButton.addClass( 'visible' );
				} else {
					$addButton.removeClass( 'visible' );
				}
			});
		},

		/**
		 * Update visibility of the Logical Operator row.
		 *
		 * Shows the operator row only when 2+ conditions are selected.
		 */
		updateOperatorVisibility: function() {
			var $operatorRow = $( '#operator-row' );

			if ( ! $operatorRow.length ) {
				return;
			}

			// Count selected conditionals.
			var selectedCount = 0;
			$( '#edit-adcode select[name="acm-conditionals[]"]' ).each( function() {
				if ( $( this ).val() ) {
					selectedCount++;
				}
			});

			if ( selectedCount >= 2 ) {
				$operatorRow.show();
			} else {
				$operatorRow.hide();
			}
		}
	};

	// Initialize when document is ready.
	$( document ).ready( function() {
		ConditionalAutocomplete.init();
	});

} )( jQuery, window.acmAutocomplete );
