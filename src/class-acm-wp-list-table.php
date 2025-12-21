<?php
/**
 * Skeleton child class of WP_List_Table
 *
 * You need to extend it for a specific provider
 * Check /Providers/class-doubleclick-for-publishers.php
 * to see example of implementation
 *
 * @since v0.1.3
 */
// Our class extends the WP_List_Table class, so we need to make sure that it's there

require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class ACM_WP_List_Table extends WP_List_Table {

	function __construct( $params = array() ) {
		parent::__construct( $params );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		/**
		 * Filters the columns displayed in the ad codes list table.
		 *
		 * Allows providers to customise which columns appear in the admin list table.
		 * Note: 'cb', 'id', 'priority', 'operator', and 'conditionals' are required
		 * and will be added automatically if missing.
		 *
		 * @since 0.1.3
		 *
		 * @param array $columns Associative array of column IDs and labels.
		 */
		$columns = apply_filters(
			'acm_list_table_columns',
			array(
				'cb'           => '<input type="checkbox" />',
				'id'           => __( 'ID', 'ad-code-manager' ),
				'name'         => __( 'Name', 'ad-code-manager' ),
				'priority'     => __( 'Priority', 'ad-code-manager' ),
				'operator'     => __( 'Logical Operator', 'ad-code-manager' ),
				'conditionals' => __( 'Conditionals', 'ad-code-manager' ),
			)
		);
		// Fail-safe for misconfiguration
		$required_before = array(
			'id' => __( 'ID', 'ad-code-manager' ),
			'cb' => '<input type="checkbox" />',
		);
		$required_after  = array(
			'priority'     => __( 'Priority', 'ad-code-manager' ),
			'operator'     => __( 'Logical Operator', 'ad-code-manager' ),
			'conditionals' => __( 'Conditionals', 'ad-code-manager' ),
		);
		$columns         = array_merge( $required_before, $columns, $required_after );
		return $columns;
	}

	/**
	 * Define bulk actions to allow users to mass delete ad codes
	 *
	 * @since 0.2.2
	 *
	 * @return array $bulk_actions All of the bulk actions permitted on the List Table
	 */
	function get_bulk_actions() {
		$bulk_actions = array(
			'delete' => __( 'Delete', 'ad-code-manager' ),
		);
		return $bulk_actions;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
		global $ad_code_manager;

		$screen = get_current_screen();

		$this->items = $ad_code_manager->get_ad_codes();

		if ( empty( $this->items ) ) {
			return;
		}

		/*
		 -- Pagination parameters -- */
		// Number of elements in your table?
		$totalitems = count( $this->items ); // return the total number of affected rows

		/**
		 * Filters the number of ad codes displayed per page in the list table.
		 *
		 * @since 0.1.3
		 *
		 * @param int $per_page Number of ad codes per page. Default 25.
		 */
		$perpage = apply_filters( 'acm_list_table_per_page', 25 );

		// Which page is this?
		$paged = ! empty( $_GET['paged'] ) ? intval( $_GET['paged'] ) : '';

		// Page Number
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1; }
		// How many pages do we have in total?

		$totalpages = ceil( $totalitems / $perpage );

		// adjust the query to take pagination into account

		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page'    => $perpage,
			) 
		);
		// The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns               = $this->get_columns();
		$hidden                = array(
			'id',
		);
		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() );

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
		$alternate_class        = ( $alternate_class == '' ? ' alternate' : '' );
		$row_class              = ' class="term-static' . esc_attr( $alternate_class ) . '"';

		echo '<tr id="ad-code-' . esc_attr( $item['post_id'] ) . '"' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Fallback column callback.
	 *
	 * @since 0.2
	 *
	 * @param object $item        Custom status as an object
	 * @param string $column_name Name of the column as registered in $this->prepare_items()
	 * @return string $output What will be rendered
	 */
	function column_default( $item, $column_name ) {
		global $ad_code_manager;

		switch ( $column_name ) {
			case 'priority':
				return esc_html( $item['priority'] );
			break;
			case 'operator':
				return ( ! empty( $item['operator'] ) ) ? $item['operator'] : $ad_code_manager->logical_operator;
			default:
				// Handle custom columns, if any.
				if ( isset( $item['url_vars'][ $column_name ] ) ) {
					$output = esc_html( $item['url_vars'][ $column_name ] );

					// Add row actions to the first data column (after cb and id).
					if ( $this->is_first_data_column( $column_name ) ) {
						$output .= $this->row_actions_output( $item );
					}

					return $output;
				}
				break;
		}
	}

	/**
	 * Check if the given column is the first data column.
	 *
	 * The first data column is the first column after 'cb' (checkbox) and 'id' (hidden).
	 * This column should display the row actions (edit/delete links).
	 *
	 * @since 0.8.0
	 *
	 * @param string $column_name The column name to check.
	 * @return bool True if this is the first data column, false otherwise.
	 */
	protected function is_first_data_column( $column_name ) {
		$columns = $this->get_columns();

		// Skip 'cb' and 'id' columns to find the first data column.
		$skip_columns = array( 'cb', 'id' );

		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, $skip_columns, true ) ) {
				continue;
			}

			// The first column we encounter after skipping is the first data column.
			return $key === $column_name;
		}

		return false;
	}

	/**
	 * Column with a checkbox
	 * Used for bulk actions
	 *
	 * @since 0.2.2
	 *
	 * @param object $item Ad code as an object
	 * @return string $output What will be rendered
	 */
	function column_cb( $item ) {
		$id     = $item['post_id'];
		$output = "<input type='checkbox' name='ad-codes[]' id='ad_code_" . esc_attr( $id ) . "' value='" . esc_attr( $id ) . "' />";
		return $output;
	}


	/**
	 *
	 */
	function column_name( $item ) {
		$output  = isset( $item['name'] ) ? esc_html( $item['name'] ) : esc_html( $item['url_vars']['name'] );
		$output .= $this->row_actions_output( $item );
		return $output;
	}

	/**
	 * Display the conditionals for this ad code
	 *
	 * @since 0.2
	 */
	function column_conditionals( $item ) {
		if ( empty( $item['conditionals'] ) ) {
			return '<em>' . __( 'None', 'ad-code-manager' ) . '</em>';
		}

		$conditionals_html = '';
		foreach ( $item['conditionals'] as $conditional ) {
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

		// Build edit URL for dedicated edit page.
		$edit_url = add_query_arg(
			array(
				'page'   => 'ad-code-manager',
				'action' => 'edit',
				'id'     => $item['post_id'],
			),
			admin_url( 'options-general.php' )
		);

		$row_actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'ad-code-manager' ) . '</a>';

		$args                  = array(
			'action' => 'acm_admin_action',
			'method' => 'delete',
			'id'     => $item['post_id'],
			'nonce'  => wp_create_nonce( 'acm-admin-action' ),
		);
		$delete_link           = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
		$row_actions['delete'] = '<a class="acm-ajax-delete" id="acm-delete-' . esc_attr( $item['post_id'] ) . '" href="' . esc_url( $delete_link ) . '">' . __( 'Delete', 'ad-code-manager' ) . '</a>';

		$output .= $this->row_actions( $row_actions );
		return $output;
	}


}
