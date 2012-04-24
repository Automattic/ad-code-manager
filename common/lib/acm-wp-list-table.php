<?php
/**
 * Skeleton child class of WP_List_Table 
 *
 * You need to extend it for a specific provider
 * Check /providers/doubleclick-for-publishers.php
 * to see example of implementation
 * 
 * @since v0.1.3
 */
//Our class extends the WP_List_Table class, so we need to make sure that it's there

	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class ACM_WP_List_Table extends WP_List_Table {
  
	function __construct( $params = array() ) {
		parent::__construct( $params );
	}
	
	/**
	* Define the columns that are going to be used in the table
	* @return array $columns, the array of columns to use with the table
	*/
	function get_columns() {
		$columns = array(
			'id'             => __( 'ID', 'ad-code-manager' ),
			'name'           => __( 'Name', 'ad-code-manager' ),
			'priority'       => __( 'Priority', 'ad-code-manager' ),
			'conditionals'   => __( 'Conditionals', 'ad-code-manager' ),
		);
		return apply_filters( 'acm_list_table_columns', $columns );
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
		$paged = !empty( $_GET["paged"] ) ? intval( $_GET["paged"] ) : '';
			
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
		$hidden = array(
				'id',
			);
		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() ) ;
	
		/**
		 * Items are set in Ad_Code_Manager class
		 * All we need to do is to prepare it for pagination
		 */
		$this->items = array_slice( $this->items, $offset, $perpage );
	}

	/**
	 * Message to be displayed if there are no ad codes found
	 *
	 * @since 0.2
	 */
	function no_items() {
		_e( 'No ad codes have been configured.', 'ad-code-manager' );
	}

	/**
	 * Prepare and echo a single ad code row
	 *
	 * @since 0.2
	 */
	function single_row( $item ) {
		static $alternate_class = '';
		$alternate_class = ( $alternate_class == '' ? ' alternate' : '' );
		$row_class = ' class="term-static' . $alternate_class . '"';

		echo '<tr id="ad-code-' . $item['post_id'] . '"' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Fallback column callback.
	 *
	 * @since 0.2
	 *
	 * @param object $item Custom status as an object
	 * @param string $column_name Name of the column as registered in $this->prepare_items()
	 * @return string $output What will be rendered
	 */
	function column_default( $item, $column_name ) {

		switch( $column_name ) {
			case 'priority':
				return esc_html( $item['priority'] );
				break;
			default:
				break;
		}

	}

	/**
	 *
	 */
	function column_name( $item ) {
		$output = esc_html( $item['name'] );
		$output .= $this->row_actions_output( $item );
		return $output;
	}

	/**
	 * Display the conditionals for this ad code
	 *
	 * @since 0.2
	 */
	function column_conditionals( $item ) {
		$conditionals_html = '';
		foreach( $item['conditionals'] as $conditional ) {
			$conditionals_html .= '<strong>' . esc_html( $conditional['function'] ) . '</strong> ' . esc_html( $conditional['arguments'][0] ) . '<br />';
		}
		return $conditionals_html;
	}

	/**
	 * Produce the action links and hidden HTML for inline editing
	 *
	 * @since 0.2
	 */
	function row_actions_output( $item ) {

		$output = '';
		$row_actions['edit'] = '<a class="acm-ajax-edit" id="acm-edit-' . $item[ 'post_id' ] . '" href="#">' . __( 'Edit', 'ad-code-manager' ) . '</a>';
		$row_actions['delete'] = '<a class="acm-ajax-delete" id="acm-delete-' . $item[ 'post_id' ] . '" href="#">' . __( 'Delete', 'ad-code-manager' ) . '</a>';
		$output .= $this->row_actions( $row_actions );

		return $output;
	}

}