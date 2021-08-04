<?php

class SimpleTags_Autolink
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    // WP_List_Table object
    public $terms_table;

    /**
     * Constructor
     *
     * @return void
     * @author Olatechpro
     */
    public function __construct()
    {

        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);

        // Javascript
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'], 11);

    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author Olatechpro
     */
    public static function admin_enqueue_scripts()
    {

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_autolinks') {
            wp_enqueue_style('st-taxonomies-css');
        }
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author Olatechpro
     */
    public function admin_menu()
    {
        $hook = add_submenu_page(
            self::MENU_SLUG,
            __('Auto Links', 'simpletags'),
            __('Auto Links', 'simpletags'),
            'simple_tags',
            'st_autolinks',
            [
                $this,
                'page_manage_autolinks',
            ]
        );

        add_action("load-$hook", [$this, 'screen_option']);
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => __('Number of items per page', 'simpletags'),
            'default' => 20,
            'option'  => 'st_autolinks_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Autolinks_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_autolinks()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);

        if (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php _e('Auto Links', 'simpletags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autolinks&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simpletags'); ?></a>

                <div class="taxopress-description">This feature can automatically add links to your content. For example, if you have a term called "WordPress", this feature will find "WordPress" in your content and add links to the archive page for that term.</div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;',
                            'simpletags') . '</span>', $search);
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Auto Links', 'simpletags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo add_query_arg('', '') ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php __('Description here.', 'simpletags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php } else {
            if ($_GET['add'] == 'new_item') {
                //add/edit taxonomy
                $this->taxopress_manage_autolinks();
                echo '<div>';
            }
        } ?>


        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
    }


    /**
     * Create our settings page output.
     *
     * @internal
     */
    public function taxopress_manage_autolinks()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $autolinks      = taxopress_get_autolink_data();
        $autolink_edit  = false;
        $autolink_limit = false;

        if ('edit' === $tab) {


            $selected_autolink = taxopress_get_current_autolink();

            if ($selected_autolink && array_key_exists($selected_autolink, $autolinks)) {
                $current       = $autolinks[$selected_autolink];
                $autolink_edit = true;
            }

        }


        if (!isset($current['title']) && count($autolinks) > 0 && apply_filters('taxopress_autolinks_create_limit',
                true)) {
            $autolink_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Auto Links', 'simpletags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="autolinks-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($autolink_edit) {
                                            echo esc_html__('Edit Auto Links', 'simpletags');
                                            echo '<input type="hidden" name="edited_autolink" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autolink[ID]" value="' . $current['ID'] . '" />';
                                        } else {
                                            echo esc_html__('Add new Auto Links', 'simpletags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($autolink_limit) {
                                            echo '<div class="st-taxonomy-content"><div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Auto Links, please upgrade to TaxoPress Pro.',
                                                    'simpletags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Auto Links. You can create Auto Links for any taxonomy.',
                                                    'simpletags') . '
                                            
                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li class="autolink_general_tab active" data-content="autolink_general">
                                                    <a href="#autolink_general"><span><?php esc_html_e('General',
                                                                'simpletags'); ?></span></a>
                                                </li>

                                                <li class="autolink_display_tab" data-content="autolink_display">
                                                    <a href="#autolink_display"><span><?php esc_html_e('Post Types',
                                                                'simpletags'); ?></span></a>
                                                </li>

                                                <li class="autolink_control_tab" data-content="autolink_control">
                                                    <a href="#autolink_control"><span><?php esc_html_e('Control',
                                                                'simpletags'); ?></span></a>
                                                </li>

                                                <li class="autolink_exceptions_tab" data-content="autolink_exceptions">
                                                    <a href="#autolink_exceptions"><span><?php esc_html_e('Exceptions',
                                                                'simpletags'); ?></span></a>
                                                </li>

                                                <li class="autolink_advanced_tab" data-content="autolink_advanced">
                                                    <a href="#autolink_advanced"><span><?php esc_html_e('Advanced',
                                                                'simpletags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table autolink_general">
                                                    <?php
                                                    echo $ui->get_tr_start();


                                                    echo $ui->get_th_start();
                                                    echo $ui->get_label('name', esc_html__('Title',
                                                            'simpletags')) . $ui->get_required_span();
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    echo $ui->get_text_input([
                                                        'namearray'   => 'taxopress_autolink',
                                                        'name'        => 'title',
                                                        'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                        'maxlength'   => '32',
                                                        'helptext'    => '',
                                                        'required'    => true,
                                                        'placeholder' => false,
                                                        'wrap'        => false,
                                                    ]);


                                                    $options = [];
                                                    foreach (get_all_taxopress_taxonomies() as $_taxonomy) {
                                                        $_taxonomy = $_taxonomy->name;
                                                        $tax       = get_taxonomy($_taxonomy);
                                                        if (empty($tax->labels->name)) {
                                                            continue;
                                                        }
                                                        if ($tax->name === 'post_tag') {
                                                            $options[] = [
                                                                'attr'    => $tax->name,
                                                                'text'    => $tax->labels->name,
                                                                'default' => 'true',
                                                            ];
                                                        } else {
                                                            $options[] = [
                                                                'attr' => $tax->name,
                                                                'text' => $tax->labels->name,
                                                            ];
                                                        }
                                                    }

                                                    $select             = [
                                                        'options' => $options,
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['taxonomy']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['taxonomy'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Taxonomy', 'simpletags'),
                                                        'required'   => true,
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => 'none',
                                                                'text'    => esc_attr__('Use case of text in content',
                                                                    'simpletags'),
                                                                'default' => 'true'
                                                            ],
                                                            [
                                                                'attr' => 'termcase',
                                                                'text' => esc_attr__('Use case of term', 'simpletags')
                                                            ],
                                                            [
                                                                'attr' => 'uppercase',
                                                                'text' => esc_attr__('All uppercase', 'simpletags')
                                                            ],
                                                            [
                                                                'attr' => 'lowercase',
                                                                'text' => esc_attr__('All lowercase', 'simpletags')
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['autolink_case']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_case'] : '';
                                                    echo $ui->get_select_number_select([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_case',
                                                        'labeltext'  => esc_html__('Auto Link case',
                                                            'simpletags'),
                                                        'selections' => $select,
                                                    ]);

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => 'post_content',
                                                                'text'    => esc_attr__('Post Content', 'simpletags'),
                                                                'default' => 'true'
                                                            ],
                                                            [
                                                                'attr' => 'post_title',
                                                                'text' => esc_attr__('Post Title', 'simpletags')
                                                            ],
                                                            [
                                                                'attr' => 'posts',
                                                                'text' => esc_attr__('Post Content and Title',
                                                                    'simpletags')
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['autolink_display']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_display'] : '';
                                                    echo $ui->get_select_number_select([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_display',
                                                        'labeltext'  => esc_html__('Auto Link areas',
                                                            'simpletags'),
                                                        'selections' => $select,
                                                    ]);


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_title_attribute',
                                                        'textvalue' => isset($current['autolink_title_attribute']) ? esc_attr($current['autolink_title_attribute']) : 'Posts tagged with %s',
                                                        'labeltext' => esc_html__('Auto Link title attribute',
                                                            'simpletags'),
                                                        'helptext'  => '',
                                                        'required'  => false,
                                                    ]);

                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table autolink_display"
                                                       style="display:none;">
                                                    <?php


                                                    /**
                                                     * Filters the arguments for post types to list for taxonomy association.
                                                     *
                                                     *
                                                     * @param array $value Array of default arguments.
                                                     */
                                                    $args = apply_filters('taxopress_attach_post_types_to_taxonomy',
                                                        ['public' => true]);

                                                    // If they don't return an array, fall back to the original default. Don't need to check for empty, because empty array is default for $args param in get_post_types anyway.
                                                    if (!is_array($args)) {
                                                        $args = ['public' => true];
                                                    }
                                                    $output = 'objects'; // Or objects.

                                                    /**
                                                     * Filters the results returned to display for available post types for taxonomy.
                                                     *
                                                     * @param array $value Array of post type objects.
                                                     * @param array $args Array of arguments for the post type query.
                                                     * @param string $output The output type we want for the results.
                                                     */
                                                    $post_types = apply_filters('taxopress_get_post_types_for_taxonomies',
                                                        get_post_types($args, $output), $args, $output);

                                                    $term_auto_locations = [];
                                                    foreach ($post_types as $post_type) {
                                                        $term_auto_locations[$post_type->name] = $post_type->label;
                                                    }

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Enable this Auto Links instance for:',
                                                            'simpletags') . '</label><br /><small style=" color: #646970;">' . esc_html__('TaxoPress will attempt to automatically Auto Links in this content. It may not be successful for all post types and layouts.',
                                                            'simpletags') . '</small></th><td>
                                                    <table class="visbile-table">';
                                                    foreach ($term_auto_locations as $key => $value) {


                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['embedded']) && is_array($current['embedded']) && in_array($key,
                                                                    $current['embedded'], true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'embedded',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';


                                                    }
                                                    echo '</table></td></tr>';


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_control"
                                                       style="display:none;">
                                                    <?php


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_usage_min',
                                                        'textvalue' => isset($current['autolink_usage_min']) ? esc_attr($current['autolink_usage_min']) : '1',
                                                        'labeltext' => esc_html__('Minimum term usage for Auto Links',
                                                            'simpletags'),
                                                        'helptext'  => __('To be included in Auto Links, a term must be used at least this many times.',
                                                            'simpletags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_usage_max',
                                                        'textvalue' => isset($current['autolink_usage_max']) ? esc_attr($current['autolink_usage_max']) : '10',
                                                        'labeltext' => esc_html__('Maximum number of links per article',
                                                            'simpletags'),
                                                        'helptext'  => __('This setting determines the maximum number of Auto Links in one post.',
                                                            'simpletags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_same_usage_max',
                                                        'textvalue' => isset($current['autolink_same_usage_max']) ? esc_attr($current['autolink_same_usage_max']) : '1',
                                                        'labeltext' => esc_html__('Maximum number of links for the same term',
                                                            'simpletags'),
                                                        'helptext'  => __('This setting determines the maximum number of Auto Links for each term in one post.',
                                                            'simpletags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_min_char',
                                                        'textvalue' => isset($current['autolink_min_char']) ? esc_attr($current['autolink_min_char']) : '',
                                                        'labeltext' => esc_html__('Minimum character length for an Auto Link',
                                                            'simpletags'),
                                                        'helptext'  => __('For example, \'4\' would only link terms that are of 4 characters or more in length.',
                                                            'simpletags'),
                                                        'min'       => '0',
                                                        'required'  => false,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_max_char',
                                                        'textvalue' => isset($current['autolink_max_char']) ? esc_attr($current['autolink_max_char']) : '',
                                                        'labeltext' => esc_html__('Maximum character length for an Auto Link',
                                                            'simpletags'),
                                                        'helptext'  => __('For example, \'4\' would only link terms that are of 4 characters or less in length.',
                                                            'simpletags'),
                                                        'min'       => '0',
                                                        'required'  => false,
                                                    ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_exceptions"
                                                       style="display:none;">
                                                    <?php


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'auto_link_exclude',
                                                        'textvalue' => isset($current['auto_link_exclude']) ? esc_attr($current['auto_link_exclude']) : '',
                                                        'labeltext' => esc_html__('Exclude some terms from tag link.',
                                                            'simpletags'),
                                                        'helptext'  => esc_html__('Example: If you enter the term "Paris", "City", the auto link tags feature will never replace these terms',
                                                            'simpletags'),
                                                        'required'  => false,
                                                    ]);


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_exclude_class',
                                                        'textvalue' => isset($current['autolink_exclude_class']) ? esc_attr($current['autolink_exclude_class']) : '',
                                                        'labeltext' => esc_html__('Exclude tags wrapped in div class/id',
                                                            'simpletags'),
                                                        'helptext'  => esc_html__('Seperate multiple entry by comma. E.g, .notag, #main-header etc',
                                                            'simpletags'),
                                                        'required'  => false,
                                                    ]);

                                                    $html_exclusions = [
                                                        //tags
                                                        'script' => esc_attr__('script', 'simpletags'),
                                                        //headers
                                                        'h1'     => esc_attr__('H1', 'simpletags'),
                                                        'h2'     => esc_attr__('H2', 'simpletags'),
                                                        'h3'     => esc_attr__('H3', 'simpletags'),
                                                        'h4'     => esc_attr__('H4', 'simpletags'),
                                                        'h5'     => esc_attr__('H5', 'simpletags'),
                                                        'h6'     => esc_attr__('H6', 'simpletags'),
                                                    ];

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Exclude Auto Links inside these elements:',
                                                            'simpletags') . '</label><br /><small style=" color: #646970;">' . esc_html__('Selecting any of these option will exlude Auto Links of terms found in-between the element tags',
                                                            'simpletags') . '</small></th><td>
                                                    <table class="visbile-table">';
                                                    foreach ($html_exclusions as $key => $value) {

                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['html_exclusion']) && is_array($current['html_exclusion']) && in_array($key,
                                                                    $current['html_exclusion'],
                                                                    true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'html_exclusion',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';

                                                        if ($key === 'script') {
                                                            echo '<tr valign="top"><th style="padding: 0;" scope="row"><hr /></th><td style="padding: 0;"><hr /></td></tr>';
                                                        }


                                                    }
                                                    echo '</table></td></tr>';

                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_advanced"
                                                       style="display:none;">
                                                    <?php


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'hook_priority',
                                                        'textvalue' => isset($current['hook_priority']) ? esc_attr($current['hook_priority']) : '12',
                                                        'labeltext' => esc_html__('Priority on the_content and the_title hook',
                                                            'simpletags'),
                                                        'helptext'  => __('For expert, possibility to change the priority of Auto Links functions on the_content hook. Useful for fix a conflict with an another plugin.',
                                                            'simpletags'),
                                                        'min'       => '1',
                                                        'required'  => false,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simpletags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simpletags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['unattached_terms'])) ? taxopress_disp_boolean($current['unattached_terms']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['unattached_terms'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'unattached_terms',
                                                        'labeltext'  => esc_html__('Add links for unattached terms',
                                                            'simpletags'),
                                                        'aftertext'  => __('By default, TaxoPress will only add Auto Links for terms that are attached to the post. If this box is checked, TaxoPress will add links for all terms',
                                                            'simpletags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simpletags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simpletags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['ignore_attached'])) ? taxopress_disp_boolean($current['ignore_attached']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['ignore_attached'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'ignore_attached',
                                                        'labeltext'  => esc_html__('Ignore attached term Auto Links',
                                                            'simpletags'),
                                                        'aftertext'  => __('Don\'t add Auto Links if the term is already assigned to the article.',
                                                            'simpletags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simpletags'),
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simpletags'),
                                                                'default' => 'true',
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autolink_dom'])) ? taxopress_disp_boolean($current['autolink_dom']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_dom'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_dom',
                                                        'labeltext'  => esc_html__('Use new Auto Links engine',
                                                            'simpletags'),
                                                        'aftertext'  => __('The new Auto Links engine uses the DOMDocument PHP class and may offer better performance. If your server does not support this functionality, TaxoPress will use the usual engine.',
                                                            'simpletags'),
                                                        'selections' => $select,
                                                    ]);

                                                    ?>
                                                </table>


                                            </div>


                                        <?php }//end new fields
                                        ?>


                                        <div class="clear"></div>


                                    </div>
                                </div>
                            </div>


                            <?php if ($autolink_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message">You're using TaxoPress Free.
                                        The Pro version has more features and support.
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank">Upgrade to Pro</a>
                                    </div>
                                </div>

                            <?php } ?>
                            <?php
                            /**
                             * Fires after the default fieldsets on the taxonomy screen.
                             *
                             * @param taxopress_admin_ui $ui Admin UI instance.
                             */
                            do_action('taxopress_taxonomy_after_fieldsets', $ui);
                            ?>

                        </div>
                    </div>


                </div>

                <div class="taxopress-right-sidebar">
                    <div class="taxopress-right-sidebar-wrapper" style="min-height: 205px;">


                        <?php
                        if (!$autolink_limit) { ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_autolink_nonce_action',
                                    'taxopress_addedit_autolink_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                           name="autolink_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Auto Links',
                                               'simpletags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                           name="autolink_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Auto Links',
                                               'simpletags')); ?>"/>
                                <?php } ?>

                                <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                       value="<?php echo esc_attr($tab); ?>"/>
                            </p>

                            <?php
                        }
                        ?>

                    </div>

                </div>

                <div class="clear"></div>


            </form>

        </div><!-- End .wrap -->

        <div class="clear"></div>
        <?php
    }

}