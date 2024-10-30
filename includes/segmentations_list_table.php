<?php

if (! defined('ABSPATH')) {
    exit();
}

require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/HD_List_Table.php';

class mpi_segmentation_list_table extends HD_List_Table{

    private $table_name;

    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mpwpi_segmentations';
        parent::__construct(array(
            'singular' => 'mpi_segmentations_field', // Singular label
            'plural' => 'mpi_segmentations_fields', // plural label, also this well be one of the table css class
            'ajax' => false
        )); // We won't support Ajax for this table
    }

    /**
     * Add extra markup in the toolbars before or after the list
     *
     * @param string $which,
     *            helps you decide if you add the markup after (bottom) or before (top) the list
     */
    public function extra_tablenav($which)
    {}

    /**
     * Define the columns that are going to be used in the table
     *
     * @return array $columns, the array of columns to use with the table
     */
    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', "morphing-portals-integration"),
            'name' => __('Name', "morphing-portals-integration"),
            'url' => __('URL', "morphing-portals-integration"),
            'segmentations' => __('Segmentations', "morphing-portals-integration")
        );
    }

    public function column_cb($item)
    {
        return sprintf('<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>',
    /*$1%s*/ $this->_args['singular'], // Let's simply repurpose the table's singular label ("video")
        /* $2%s */
        $item->id); // The value of the checkbox should be the record's id
    }

    /**
     * Returns the list of available bulk actions.
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete',
            'force_delete' => 'Force Delete'
        );
        return $actions;
    }

    /**
     * How the bulk actions are processed for this table.
     */
    public function process_bulk_action(){

        if ('delete' === $this->current_action()) {
            foreach ($_GET[$this->_args['singular']] as $item) {
                $this->delete_this_segmentation($item);
            }
        }

        if ('force_delete' === $this->current_action()) {
            foreach ($_GET[$this->_args['singular']] as $item) {
                $this->force_delete_this_segmentation($item);
            }
        }
    }

    /**
     * Checks between action and action2.
     */
    public function current_action()
    {
        if (isset($_REQUEST['action']) && - 1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && - 1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @return array $sortable, the array of columns that can be sorted by the user
     *
     */
    public function get_sortable_columns()
    {
        return array(
            'id' => array(
                'id',
                false
            ),
            'name' => array(
                'name',
                false
            )
        );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    public function prepare_items()
    {
        global $wpdb;
        $screen = get_current_screen();

        /* -- Check if there are bulk actions -- */
        $this->process_bulk_action();

        /* -- Preparing your query -- */
        $query = "SELECT * FROM $this->table_name";

        /* -- Ordering parameters -- */
        // Parameters that are going to be used to order the result
        $orderby = ! empty($_GET["orderby"]) ? mysqli_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = ! empty($_GET["order"]) ? mysqli_real_escape_string($_GET["order"]) : '';
        if (! empty($orderby) & ! empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        /* -- Pagination parameters -- */
        // Number of elements in your table?
        $totalitems = $wpdb->query($query); // return the total number of affected rows
                                            // How many to display per page?
        $perpage = 25;

        // Which page is this?
        $paged = ! empty($_GET["paged"]) ? mysqli_real_escape_string($_GET["paged"]) : '';

        // Page Number
        if (empty($paged) || ! is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }

        // How many pages do we have in total?
        $totalpages = ceil($totalitems / $perpage);

        // adjust the query to take pagination into account
        if (! empty($paged) && ! empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage
        ));

        // The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable
        );

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }

    /**
     * Display the rows of records in the table
     *
     * @return string, echo the markup of the rows
     *
     */
    public function display_rows()
    {

        // Get the records registered in the prepare_items method
        $records = $this->items;

        // Get the columns registered in the get_columns and get_sortable_columns methods
        list ($columns, $hidden) = $this->get_column_info();

        // Loop for each record
        if (! empty($records)) {
            foreach ($records as $rec) {
                // Open the line
                echo '<tr id="record_' . $rec->id . '">';

                foreach ($columns as $column_name => $column_display_name) {

                    // Style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = "";
                    if (in_array($column_name, $hidden))
                        $style = ' style="display:none;"';
                    $attributes = $class . $style;

                    // edit link
                    $editlink = menu_page_url("morphing-portals-integration-add-segmentation", 0) . '&segmentation_id=' . (int) $rec->id;

                    // Display the cell
                    switch ($column_name) {
                        case "id":
                            echo '<td ' . $attributes . '>' . '<a href="' . $editlink . '">'  . stripslashes($rec->id) . '</a></td>';
                            break;
                        case "name":
                            echo '<td ' . $attributes . '>' . stripslashes($rec->name) . '</td>';
                            break;
                        case "url":
                            echo '<td ' . $attributes . '>' . stripslashes($rec->URL) . '</td>';
                            break;
                        case "segmentations":
                            echo '<td ' . $attributes . '>' . stripslashes($rec->user_segmentations) . '</td>';
                            break;
                        case "cb":
                            echo $this->column_cb($rec);
                            break;
                    }
                }

                // Close the line
                echo '</tr>';
            }
        }
    }

    public function delete_this_segmentation($id){

	    $api_token = get_option('hd_mpi_api_token');
	    $endpoint = MORPHING_PORTALS_WP_API_SEGMENTATION_ENDPOINT;

	    $segmentation_obj = new MP_Segmentation_API(array('id' => $id));
  		$segmentation_obj->search_mp_id();

  		$endpoint .= "/" . $segmentation_obj->get_mp_id();
  		$url       = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

	    $comunicator = new MP_API_COMM($url, $api_token);
	    $result = $comunicator->deleteEntry('userSegmentation');

	    $comunicator->close();

	    if($result){
	        global $wpdb;
	        $query = $wpdb->prepare("DELETE FROM $this->table_name WHERE id = %d", $id);
	        $wpdb->query($query);
        }
    }

    public function force_delete_this_segmentation($id){
        global $wpdb;
        $query = $wpdb->prepare("DELETE FROM $this->table_name WHERE id = %d", $id);
        $wpdb->query($query);
    }

}
