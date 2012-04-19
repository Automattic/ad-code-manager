<?php
/**
 * Template file for Ad Code Manager
 *
 * @todo TODO: Implement UI interactions
 */
?>
<div class="wrap nosubsub">
<div id="col-container">

<div id="col-right">
<div class="col-wrap">
<?php
	$this->wp_list_table->prepare_items();
	$this->wp_list_table->display();
?>	

</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">


<div class="form-wrap">
<h3>Add New Ad Code</h3>
<form id="add-adcode" method="post" action="<?php echo home_url( '?acm-request=true&acm-action=edit' ) ?>" class="validate">
<input type="hidden" name="oper" value="add" />
<input type="hidden" name="priority" value="10" />
<input type="hidden" name="conditionals" />
<?php wp_nonce_field('acm_nonce', 'acm-nonce') ?>

<?php
foreach ( $this->current_provider->columns as $slug => $title ):
?>
<div class="form-field form-required">
	<label for="acm-<?php echo esc_attr( $slug ) ?>"><?php echo esc_html( $title ) ?></label>
	<input name="<?php echo esc_attr( $slug ) ?>" id="acm-<?php echo esc_attr( $slug ) ?>" type="text" value="" size="40" aria-required="true">
</div>
<?php
endforeach;
?>
<div class="form-field" id="conditional-tpl">
	<div class="form-new-row">
	<label for="acm-conditionals">Conditional</label>
	<select name="conditionals[0][function]" id="acm-conditional-0">
<option value="">Select conditional</option>	  
<?php
foreach ( $this->whitelisted_conditionals as $key ):
?>
<option value="<?php echo esc_attr($key) ?>"><?php echo esc_html( ucfirst( str_replace('_', ' ', $key ) ) ) ?></option>
<?php endforeach; ?>
	</select>
	</div>
	<div class="form-half">
		<label>&nbsp;</label>
		<input name="conditionals[0][arguments]" id="acm-argument-0" type="text" value="" size="20">
	</div>  
</div>
<div class="form-field form-add-more">
	<a href="javascript:;" id="add-more-conditionals">Add more</a>
</div>
<p class="clear"></p>
<?php submit_button( __( 'Add New Ad Code', 'ad-code-manager' ) ); ?>
</form></div>

</div>
</div><!-- /col-left -->

</div>
</div>