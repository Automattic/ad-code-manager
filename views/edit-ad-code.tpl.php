<?php
/**
 * Template file for Ad Code Manager Edit Page
 *
 * Variables available from render_edit_page():
 * - $ad_code: Array containing the ad code data
 * - $ad_code_id: The ad code post ID
 *
 * @package Automattic\AdCodeManager
 * @since 0.10.0
 */

// Get the main page URL for redirects and back link.
$main_page_url = admin_url( 'options-general.php?page=ad-code-manager' );
?>
<div class="wrap">
	<h1>
		<?php esc_html_e( 'Edit Ad Code', 'ad-code-manager' ); ?>
		<a href="<?php echo esc_url( $main_page_url ); ?>" class="page-title-action">
			<?php esc_html_e( 'Back to Ad Codes', 'ad-code-manager' ); ?>
		</a>
	</h1>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just displaying a message.
	if ( isset( $_REQUEST['message'] ) ) {
		$message_text  = '';
		$message_class = 'success';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch ( $_REQUEST['message'] ) {
			case 'ad-code-updated':
				$message_text = __( 'Ad code updated.', 'ad-code-manager' );
				break;
			case 'validation-error':
				$transient_key = 'acm_validation_error_' . get_current_user_id();
				$message_text  = get_transient( $transient_key );
				if ( $message_text ) {
					delete_transient( $transient_key );
					$message_class = 'error';
				}
				break;
			default:
				$message_text = '';
				break;
		}
		if ( '' !== $message_text ) {
			echo '<div class="notice notice-' . esc_attr( $message_class ) . ' is-dismissible"><p>' . esc_html( $message_text ) . '</p></div>';
		}
	}
	?>

	<form id="edit-adcode" method="POST" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="validate">
		<input type="hidden" name="action" value="acm_admin_action" />
		<input type="hidden" name="method" value="edit" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $ad_code_id ); ?>" />
		<?php wp_nonce_field( 'acm-admin-action', 'nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<?php
				// URL Variables / Ad Code Arguments.
				foreach ( $this->current_provider->ad_code_args as $arg ) :
					if ( ! $arg['editable'] ) {
						continue;
					}
					$column_id     = 'acm-column[' . $arg['key'] . ']';
					$current_value = isset( $ad_code['url_vars'][ $arg['key'] ] )
						? $ad_code['url_vars'][ $arg['key'] ]
						: '';
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $column_id ); ?>">
								<?php echo esc_html( $arg['label'] ); ?>
								<?php if ( $arg['required'] ) : ?>
									<span class="required">*</span>
								<?php endif; ?>
							</label>
						</th>
						<td>
							<?php if ( isset( $arg['type'] ) && 'select' === $arg['type'] ) : ?>
								<select name="<?php echo esc_attr( $column_id ); ?>"
										id="<?php echo esc_attr( $column_id ); ?>"
										aria-required="<?php echo esc_attr( $arg['required'] ); ?>">
									<?php foreach ( $arg['options'] as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>"
											<?php selected( $current_value, $value ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input type="text"
									   name="<?php echo esc_attr( $column_id ); ?>"
									   id="<?php echo esc_attr( $column_id ); ?>"
									   value="<?php echo esc_attr( $current_value ); ?>"
									   class="regular-text"
									   aria-required="<?php echo esc_attr( $arg['required'] ); ?>" />
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<!-- Priority -->
				<tr>
					<th scope="row">
						<label for="priority"><?php esc_html_e( 'Priority', 'ad-code-manager' ); ?></label>
					</th>
					<td>
						<input type="number"
							   name="priority"
							   id="priority"
							   value="<?php echo esc_attr( $ad_code['priority'] ); ?>"
							   class="small-text" />
						<p class="description">
							<?php esc_html_e( 'Lower numbers have higher priority.', 'ad-code-manager' ); ?>
						</p>
					</td>
				</tr>

				<!-- Conditionals -->
				<tr>
					<th scope="row"><?php esc_html_e( 'Conditionals', 'ad-code-manager' ); ?></th>
					<td>
						<div class="acm-conditional-fields" id="edit-conditional-tpl">
							<div class="form-new-row">
								<?php if ( ! empty( $ad_code['conditionals'] ) ) : ?>
									<?php foreach ( $ad_code['conditionals'] as $index => $conditional ) : ?>
										<div class="conditional-single-field"<?php echo 0 === $index ? ' id="conditional-single-field-master"' : ''; ?>>
											<div class="conditional-function">
												<select name="acm-conditionals[]">
													<option value="">
														<?php esc_html_e( 'Select conditional', 'ad-code-manager' ); ?>
													</option>
													<?php foreach ( $this->whitelisted_conditionals as $key ) : ?>
														<option value="<?php echo esc_attr( $key ); ?>"
															<?php selected( $conditional['function'], $key ); ?>>
															<?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>
														</option>
													<?php endforeach; ?>
												</select>
											</div>
											<div class="conditional-arguments">
												<input name="acm-arguments[]"
													   type="text"
													   value="<?php echo esc_attr( is_array( $conditional['arguments'] ) ? implode( ';', $conditional['arguments'] ) : $conditional['arguments'] ); ?>"
													   size="20" />
												<?php if ( $index > 0 ) : ?>
													<a href="#" class="acm-remove-conditional">
														<?php esc_html_e( 'Remove', 'ad-code-manager' ); ?>
													</a>
												<?php endif; ?>
											</div>
										</div>
									<?php endforeach; ?>
								<?php else : ?>
									<!-- Empty conditional row for adding new -->
									<div class="conditional-single-field" id="conditional-single-field-master">
										<div class="conditional-function">
											<select name="acm-conditionals[]">
												<option value="">
													<?php esc_html_e( 'Select conditional', 'ad-code-manager' ); ?>
												</option>
												<?php foreach ( $this->whitelisted_conditionals as $key ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>">
														<?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="conditional-arguments">
											<input name="acm-arguments[]" type="text" value="" size="20" />
										</div>
									</div>
								<?php endif; ?>
							</div>
							<div class="form-field form-add-more <?php echo ! empty( $ad_code['conditionals'] ) ? 'visible' : ''; ?>">
								<a href="#" class="button button-secondary add-more-conditionals">
									<?php esc_html_e( 'Add another condition', 'ad-code-manager' ); ?>
								</a>
							</div>
						</div>
					</td>
				</tr>

				<!-- Logical Operator -->
				<?php $show_operator = count( $ad_code['conditionals'] ) >= 2; ?>
				<tr id="operator-row" <?php echo ! $show_operator ? 'style="display:none;"' : ''; ?>>
					<th scope="row">
						<label for="operator"><?php esc_html_e( 'Logical Operator', 'ad-code-manager' ); ?></label>
					</th>
					<td>
						<select name="operator" id="operator">
							<option value="OR" <?php selected( $ad_code['operator'], 'OR' ); ?>>
								<?php esc_html_e( 'OR (any condition)', 'ad-code-manager' ); ?>
							</option>
							<option value="AND" <?php selected( $ad_code['operator'], 'AND' ); ?>>
								<?php esc_html_e( 'AND (all conditions)', 'ad-code-manager' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'How multiple conditionals are evaluated.', 'ad-code-manager' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Update Ad Code', 'ad-code-manager' ) ); ?>
	</form>
</div>
