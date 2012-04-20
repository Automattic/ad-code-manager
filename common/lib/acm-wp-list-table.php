<?php
/**
 * Skeleton child class of WP_List_Table 
 *
 * You need to extend it for a specific provider
 * 
 * @since v0.1.3
 */
//Our class extends the WP_List_Table class, so we need to make sure that it's there
//if( ! class_exists( 'WP_List_Table' ) ){
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
//}
class ACM_WP_List_Table extends WP_List_Table {
  
	//public $ad_codes;
	
	function __construct( $params = array() ) {
		parent::__construct( $params );
	}
	
	/**
	* Define the columns that are going to be used in the table
	* @return array $columns, the array of columns to use with the table
	*/
   function get_columns() {
	   return $columns= array(
		   'col_acm_id'=>__( 'ID' ),
		   'col_acm_name'=>__( 'Name' ),
		   'col_acm_priority'=>__( 'Priority' ),
	   );
   }

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
		//global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		if ( empty( $this->items ) )
			return;

		/* -- Pagination parameters -- */
		//Number of elements in your table?
		$totalitems = count( $this->items ); //return the total number of affected rows
		
		//How many to display per page?
		$perpage = 25;
		
		//Which page is this?
		$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
			
		//Page Number
		if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
		//How many pages do we have in total?
		
		$totalpages = ceil($totalitems/$perpage);
		
		//adjust the query to take pagination into account
		
		if( ! empty( $paged ) && !empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
		}
	
		/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage,
			) );
		//The pagination links are automatically built according to those parameters
	
		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$this->_column_headers = array($columns, array( 'col_acm_post_id' ), $this->get_sortable_columns() ) ;
	
		/**
		 * Items are set in Ad_Code_Manager class
		 * All we need to do is to prepare it for pagination
		 */
		$this->items = array_slice( $this->items, $offset, $perpage );
	}

	/**
	 * Display the rows of records in the table
     * @return string, echo the markup of the rows
     */
	function display_rows() {
		// Alternate the table row classes for color swapping
		$alternate = '';

		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		//Loop for each record
		if( ! empty( $records ) ) { foreach( $records as $rec )  {

			//edit link
			$edit_link  = add_query_arg( array(
				'acm-request' => true,
				'acm-action' => 'edit',
				'acm_id' => (int) $rec['post_id']
			), home_url( '/' ) );

			$alternate = 'alternate' == $alternate ? '' : 'alternate';

			// Gather a new set of inputs for the inline editor on each loop
			$inline_edit_inputs = '';

			//Open the line
	        echo '<tr id="record_'.$rec['post_id'].'" class="' . $alternate . ' acm-record-display">';
			$i = 0;
			foreach ( $columns as $column_name => $column_display_name ) {

				//Style attributes for each col
				$class = "class='$column_name column-$column_name'";
				$style = "";

				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';
				else
					$i++; // Only increment on visible columns to help display row actions and ajax

				$attributes = $class . $style;

				$key = str_replace('col_acm_', '', $column_name );
			
				if ( ! isset( $rec[$key] ) && ! isset( $rec['url_vars'][$key] ) )
					continue;
			
				$value = isset( $rec[$key] ) ? $rec[$key] : $rec['url_vars'][$key];
				$extra = '';
				if ( 1 == $i ) {
					// @todo Only Edit is hooked up via JS right now
					$extra .= $this->row_actions( array(
						'View Conditionals' => '<a class="acm-ajax-view" id="acmview-' . $rec[ 'post_id' ] . '" href="#">View Conditionals</a>',
						'Edit' => '<a class="acm-ajax-edit" id="acmedit-' . $rec[ 'post_id' ] . '" href="#">Edit</a>',
						'Delete' => '<a class="acm-ajax-delete" id="acmdelete-' . $rec[ 'post_id' ] . '" href="#">Delete</a>',
					));
				}

				echo '<td '.$attributes.'>'.stripslashes( $value ). $extra . '</td>';

				if ( in_array( $column_name, $hidden ) )
					continue;

				// If this is *not* a hidden field, we also want to include it in the inline editor
				$inline_edit_inputs .= '<div class="acm-edit-field"><label for="col_edit_' . $key . '_' . $rec[ 'post_id' ] . '">' . $column_display_name . '</label>';
				$inline_edit_inputs .= '<input type="text" name="' . $key . '" id="col_edit_' . $key . '_' . $rec[ 'post_id' ] . '" value="' . esc_attr( $value ) . '" /></div>';
			}

			//Close the line
			echo'</tr>';

			// Gather the conditional functions/arguments
			$conditionals = get_post_meta( $rec[ 'post_id' ], 'conditionals', true );
			$inline_edit_conditionals = '';
			if ( ! empty( $conditionals ) ) {
				for( $j=0, $total = sizeof( $conditionals ); $j < $total; $j++ ) {
					$inline_edit_conditionals .= '<div class="acm-edit-cond"><select name="conditionals[' . $j . '][function]" class="cond_' . $rec[ 'post_id' ] . '"><option value="' . esc_attr( $conditionals[$j][ 'function' ] ) . '">' . $conditionals[$j][ 'function' ] . '</option></select>';

					if ( ! empty( $conditionals[$j][ 'arguments' ][0] ) ) {
						$inline_edit_conditionals .= '<input name="conditionals[' . $j . '][arguments]" type="text" size="20" value="' . esc_attr( $conditionals[$j][ 'arguments' ][0] ) . '" />';
					} else {
						$inline_edit_conditionals .= '<input name="conditionals[' . $j . '][arguments]" type="text" size="20" value="" />';
					}

					$inline_edit_conditionals .= '</div>';
				}
			}

			// Display the hidden row for inline editing
			?>
			<tr class="<?php echo $alternate; ?> acm-edit-display" id="record_display_<?php echo $rec[ 'post_id' ]; ?>" style="display:none;" >
				<td colspan="<?php echo $i; ?>">
					<form id="acm-edit-form-<?php echo $rec[ 'post_id' ]; ?>" method="POST" action="<?php echo $edit_link; ?>">
						<fieldset><div class="inline-edit-col">
							<input type="hidden" name="id" value="<?php echo esc_attr( $rec[ 'post_id' ] ); ?>">
							<input type="hidden" name="oper" value="edit">
							<?php wp_nonce_field( 'acm_nonce', 'acm-nonce' ); ?>
							<?php echo $inline_edit_inputs; ?>
							<label class="acm-conditional-label" for="acm-conditionals">Conditionals:</label>
							<?php echo $inline_edit_conditionals; ?>
						</div></fieldset>
						<p class="inline-edit-save submit">
							<?php submit_button( __( 'Cancel', 'ad-code-manager' ), 'secondary', 'acm-cancel-edit-' . $rec[ 'post_id' ], false ); ?> 
							<?php submit_button( __( 'Update', 'ad-code-manager' ), 'primary', 'acm-edit-button', false ); ?>
						</p>
					</form>
				</td>
			</tr>
			<?php
		}}
	}

}