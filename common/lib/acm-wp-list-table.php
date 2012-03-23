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

	//Get the records registered in the prepare_items method
	$records = $this->items;

	//Get the columns registered in the get_columns and get_sortable_columns methods
	list( $columns, $hidden ) = $this->get_column_info();
	

	//Loop for each record
	if( ! empty( $records ) ) { foreach( $records as $rec )  {

		//Open the line
        echo '<tr id="record_'.$rec['post_id'].'">';
		foreach ( $columns as $column_name => $column_display_name ) {

			//Style attributes for each col
			$class = "class='$column_name column-$column_name'";
			$style = "";
			if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
			$attributes = $class . $style;

			//edit link
			$editlink  = '/wp-admin/tools.php?page=ad-code-manager&acm-action=edit&id='.(int)$rec['post_id'];

			$key = str_replace('col_acm_', '', $column_name );
			
			if ( ! isset( $rec[$key] ) && ! isset( $rec['url_vars'][$key] ) )
				continue;
			
			$value = isset( $rec[$key] ) ? $rec[$key] : $rec['url_vars'][$key];
			
			echo '<td '.$attributes.'>'.stripslashes( $value ).'</td>';
			
		}

		//Close the line
		echo'</tr>';
	}}
}

}