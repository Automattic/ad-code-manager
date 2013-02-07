<?php
/**
 * Template file for Ad Code Manager
 */
?>
	<div class="acm-ui-wrapper wrap">
	<h2>Ad Code Manager</h2>
	<?php if ( isset( $_REQUEST['message'] ) ) {
		switch( $_REQUEST['message'] ) {
			case 'ad-code-added':
				$message_text = __( 'Ad code created.', 'ad-code-manager' );
				break;
			case 'ad-code-deleted':
				$message_text = __( 'Ad code deleted.', 'ad-code-manager' );
				break;
			case 'ad-codes-deleted':
				$message_text = __( 'Ad codes deleted.', 'ad-code-manager' );
				break;
			case 'options-saved':
				$message_text = __( 'Options saved.', 'ad-code-manager' );
				break;
			default:
				$message_text = '';
				break;
		}
		if ( $message_text )
			echo '<div class="message updated"><p>' . esc_html( $message_text ) . '</p></div>';
	} ?>
	<p> Refer to help section for more information</p>
	</div>

<div class="wrap nosubsub">
<div id="col-container">

<div id="col-right">
<div class="col-wrap">
	<form action="" method="post" name="updateadcodes" id="updateadcodes">
<?php
	wp_nonce_field( 'acm-bulk-action', 'bulk-action-nonce' );
	$this->wp_list_table->prepare_items();
	$this->wp_list_table->display();
?>
	</form>

</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">


<div class="form-wrap">
<h3><?php _e( 'Add New Ad Code', 'ad-code-manager' ); ?></h3>
<form id="add-adcode" method="POST" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="validate">
<input type="hidden" name="action" value="acm_admin_action" />
<input type="hidden" name="method" value="add" />
<input type="hidden" name="priority" value="10" />
<?php wp_nonce_field( 'acm-admin-action', 'nonce' ); ?>

<?php
foreach ( $this->current_provider->ad_code_args as $arg ):
	if ( ! $arg['editable'] )
		continue;

	$column_id = 'acm-column[' . $arg['key'] . ']';

	/*
	 * Field type conditional: Defaults to text
	 *
	 * This is needed for Enterprise implementation since users can't add PHP
	 * to theme files. Position in theme needs to be from a predefined set of
	 * options (do_action calls within the theme).
	 */
	if ( isset( $arg['type'] ) && 'select' == $arg['type'] ) :
?>
<div class="form-field form-required">
    <label for="<?php echo esc_attr( $column_id ) ?>"><?php echo esc_html( $arg['label'] ) ?></label>
    <select name="<?php echo esc_attr( $column_id ) ?>" id="<?php echo esc_attr( $column_id ) ?>" aria-required="<?php echo $arg['required'] ?>">
		<?php foreach ( $arg['options'] as $value => $label ) : ?>
		<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
</div>
	<?php
	else : // field_type conditional
	?>
<div class="form-field form-required">
	<label for="<?php echo esc_attr( $column_id ) ?>"><?php echo esc_html( $arg['label'] ) ?></label>
	<input name="<?php echo esc_attr( $column_id ) ?>" id="<?php echo esc_attr( $column_id ) ?>" type="text" value="" size="40" aria-required="<?php echo $arg['required'] ?>">
</div>
<?php
	endif;
endforeach;
?>
<div class="form-field acm-conditional-fields" id="conditional-tpl">
	<div class="form-new-row">
	<label for="acm-conditionals"><?php _e( 'Conditionals', 'ad-code-manager' ); ?></label>
	<div class="conditional-single-field" id="conditional-single-field-master">
	<div class="conditional-function">
	<select name="acm-conditionals[]">
<option value=""><?php _e( 'Select conditional', 'ad-code-manager' ); ?></option>
<?php
foreach ( $this->whitelisted_conditionals as $key ):
?>
<option value="<?php echo esc_attr($key) ?>"><?php echo esc_html( ucfirst( str_replace('_', ' ', $key ) ) ) ?></option>
<?php endforeach; ?>
	</select>
	</div>
	<div class="conditional-arguments">
		<input name="acm-arguments[]" type="text" value="" size="20" />
	</div>
	</div>
</div>
<div class="form-field form-add-more">
	<a href="#" class="button button-secondary add-more-conditionals">Add more</a>
</div>
</div>
<p class="clear"></p>
<?php submit_button( __( 'Add New Ad Code', 'ad-code-manager' ) ); ?>
</form></div>

</div>
</div><!-- /col-left -->

<div class="acm-global-options">
	<h3><?php _e( 'Ad Code Manager Options', 'ad-code-manager' ); ?></h3>
	<div class="form-wrap">
        <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" name="updatesettings" id="updatesettings">
			<div class="form-field form-required">
				<label for="provider">Provider</label>
				<select name="provider" id="provider">
					<?php
					$current_provider = $this->get_option( 'provider', self::DEFAULT_PROVIDER );
					foreach ( $this->providers as $slug => $provider ) :
						?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( esc_attr( $slug ), $current_provider ); ?>>
						<?php echo esc_html( $provider['label'] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php do_action( 'acm_provider_options' ); ?>
            <input type="hidden" name="action" value="acm_admin_action" />
			<input type="hidden" name="method" value="update_options" />
            <input type="hidden" name="priority" value="10" />
			<?php wp_nonce_field( 'acm-admin-action', 'nonce' ); ?>
			<?php submit_button( __( 'Save Options', 'ad-code-manager' ) ); ?>
		</form>
	</div>
</div>

<?php $this->wp_list_table->inline_edit(); ?>

</div>
</div>