<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class TagClouds_List extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => __('Tag Cloud', 'simpletags'), //singular name of the listed records
            'plural'   => __('Tag Clouds', 'simpletags'), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ]);

    }

    /**
     * Retrieve st_tagclouds data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_st_tagclouds()
    {
        return taxopress_get_tagcloud_data();
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        return count(taxopress_get_tagcloud_data());
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['st-cloudtag-tr'];
        $id    = 'st-cloudtag-' . md5($item['ID']);
        echo sprintf('<tr id="%s" class="%s">', $id, implode(' ', $class));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'title'        => __('Title', 'simpletags'),
            'taxonomy'     => __('Taxonomy', 'simpletags'),
            'max'          => __('Max tags to display', 'simpletags'),
            'limit_days'   => __('Timeframe', 'simpletags'),
            'shortcode'    => __('Shortcode', 'simpletags')
        ];

        return $columns;
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return !empty($item[$column_name]) ? $item[$column_name] : '&mdash;';
    }

    /** Text displayed when no stterm data is available */
    public function no_items()
    {
        _e('No item avaliable.', 'simpletags');
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     *
     *
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        if (!empty($_REQUEST['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('st_tagclouds_per_page', 20);

        /**
         * Fetch the data
         */
        $data = self::get_st_tagclouds();

        /**
         * Handle search
         */
        if ((!empty($_REQUEST['s'])) && $search = $_REQUEST['s']) {
            $data_filtered = [];
            foreach ($data as $item) {
                if ($this->str_contains($item['title'], $search, false) || $this->str_contains($item['taxonomy'], $search,
                        false)) {
                    $data_filtered[] = $item;
                }
            }
            $data = $data_filtered;
        }

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a, $b)
        {
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID'; //If no sort, default to role
            $order   = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc
            $result  = strnatcasecmp($a[$orderby],
                $b[$orderby]); //Determine sort order, case insensitive, natural order

            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items  = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page'    => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $sensitive Use case sensitive search
     *
     * @return bool
     */
    public function str_contains($haystack, $needles, $sensitive = true)
    {
        foreach ((array)$needles as $needle) {
            $function = $sensitive ? 'mb_strpos' : 'mb_stripos';
            if ($needle !== '' && $function($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'title'        => ['title', true],
            'taxonomy'     => ['taxonomy', true],
            'max'          => ['max', true],
        ];

        return $sortable_columns;
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        //Build row actions
        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'               => 'st_tagclouds',
                        'add'                => 'new_item',
                        'action'             => 'edit',
                        'taxopress_tagcloud' => $item['ID'],
                    ],
                    admin_url('admin.php')
                ),
                __('Edit', 'simpletags')
            ),
            'delete' => sprintf(
                    '<a href="%s" class="delete-tagcloud">%s</a>',
                    add_query_arg([
                        'page'     => 'st_tagclouds',
                        'action'   => 'taxopress-delete-tagcloud',
                        'taxopress_tagcloud' => esc_attr($item['ID']),
                        '_wpnonce' => wp_create_nonce('tagcloud-action-request-nonce')
                    ],
                        admin_url('admin.php')),
                    __('Delete', 'simpletags')
                ),
        ];

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for title column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_title($item)
    {
        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>',
            add_query_arg(
                [
                    'page'               => 'st_tagclouds',
                    'add'                => 'new_item',
                    'action'             => 'edit',
                    'taxopress_tagcloud' => $item['ID'],
                ],
                admin_url('admin.php')
            ),
            esc_html($item['title'])
        );

        return $title;
    }

    /**
     * Method for taxonomy column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_taxonomy($item)
    {
        $taxonomy = get_taxonomy( $item['taxonomy'] );
        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>',
            add_query_arg(
                [
                    'taxonomy' => $item['taxonomy'],
                ],
                admin_url('edit-tags.php')
            ),
            esc_html($taxonomy->labels->name)
        );

        return $title;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_max($item)
    {
        return $item['max'];
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_limit_days($item)
    {

        $days_options = [
            '1' => esc_attr__( '24 hours', 'simpletags' ),
            '7' => esc_attr__( '7 days', 'simpletags' ),
            '14' => esc_attr__( '2 weeks', 'simpletags' ),
            '30' => esc_attr__( '1 month', 'simpletags' ),
            '180' => esc_attr__( '6 months', 'simpletags' ),
            '365' => esc_attr__( '1 year', 'simpletags' ),
            '0' => esc_attr__( 'None', 'simpletags' ),
        ];
        
        return $days_options[$item['limit_days']];
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_shortcode($item)
    {

        return '<input type="text" value=\'[taxopress_tagcloud id="'.$item['ID'].'"]\' />';
    }


}
