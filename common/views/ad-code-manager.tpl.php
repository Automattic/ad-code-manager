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
<form id="add-adcode" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" class="validate">
<input type="hidden" name="action" value="add-adcode">
<input type="hidden" id="_wpnonce_add-tag" name="_wpnonce_add-tag" value="82a319d7b2"><input type="hidden" name="_wp_http_referer" value="/wp-admin/edit-tags.php?taxonomy=category">

<?php
foreach ( $this->current_provider->columns as $slug => $title ):
?>
<div class="form-field form-required">
	<label for="acm-<?php echo esc_attr( $slug ) ?>"><?php echo esc_html( $title ) ?></label>
	<input name="acm-<?php echo esc_attr( $slug ) ?>" id="acm-<?php echo esc_attr( $slug ) ?>" type="text" value="" size="40" aria-required="true">
</div>
<?php
endforeach;
?>

<p class="submit"><input type="submit" name="submit" id="submit" class="button" value="Add New Ad Code"></p></form></div>

</div>
</div><!-- /col-left -->

</div>
</div>