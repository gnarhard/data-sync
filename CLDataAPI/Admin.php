<?php
/**
 * Register the Locations post type
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/admin
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Admin
{
	// the instance of this object
	private static $instance;

	/**
	 * Initialize actions and filter functions
	 *
	 * @access public
	 * @static
	 */
	public static function init()
	{
		add_action( 'init', array( __CLASS__, 'ngeek_custom_post_register' ) );
		add_action( 'init', array( __CLASS__, 'ngeek_region_taxonomy_register' ) );
		add_action( 'init', array( __CLASS__, 'ngeek_state_taxonomy_register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'ngeek_remove_location_edit_boxes' ) );
		add_filter( 'bulk_actions-edit-cl_location', array( __CLASS__, 'locations_receiver_bulk_actions' ) );
		add_action( 'admin_head', array( __CLASS__, 'remove_date_drop') );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'locations_receiver_admin_css' ) );
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'locations_receiver_admin_css' ) );
		add_action( 'admin_notices',  array( __CLASS__, 'cl_locations_site_id_alert' ) );
		add_filter( 'acf/settings/load_json', array( __CLASS__, 'locations_receiver_acf_json' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'ngeek_edit_action_row'), 10, 2  );
		add_action( 'edit_form_top', array( __CLASS__, 'custom_locations_post_page' ), 10, 1 );
		add_filter( 'manage_cl_location_posts_columns' , array( __CLASS__, 'locations_receiver_set_admin_column_list' ) );
		add_action( 'manage_cl_location_posts_custom_column' , array( __CLASS__, 'locations_receiver_populate_custom_columns'), 10, 2 );
		add_filter( 'manage_edit-cl_location_sortable_columns' , array( __CLASS__, 'locations_receiver_sortable_columns' ) );
		add_action( 'admin_menu', array( __CLASS__, 'ngeek_register_cllocation_settings_page' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'locations_receiver_admin_posts_filter_restrict_manage_posts' ) );
		add_filter( 'parse_query', array( __CLASS__, 'locations_receiver_posts_filter' ) );
	}

	/**
	 * Add path from plugin folder to control ACF fields
	 *
	 * @access public
	 * @static
	 * @param array $paths
	 * @return array $paths
	 */
	public function locations_receiver_acf_json( $paths ) {

	    $paths[] = CLDataAPI::get_instance()->settings->path . '/acf-json';

	    return $paths;

	}

	/**
	 * Register Location post type
	 *
	 * @access public
	 * @static
	 */
	public static function ngeek_custom_post_register() {
		$ngeek_cpt_id = 'cl_location'; // Slug name
		$ngeek_cpt_slug_name = 'location'; // Permalink
		$ngeek_cpt_menu_name = 'Locations'; // Menu name
		$ngeek_cpt_single_name = 'Location'; // Singular name
		$ngeek_cpt_plural_name = 'Locations'; // Plural name

		register_post_type( $ngeek_cpt_id,
			array('labels' => array(
				'name' => __($ngeek_cpt_menu_name, 'ngeektheme'),
				'singular_name' => __($ngeek_cpt_single_name, 'ngeektheme'),
				'all_items' => __('All ' . $ngeek_cpt_plural_name, 'ngeektheme'),
				'add_new' => __('Add New', 'ngeektheme'),
				'add_new_item' => __('Add New ' . $ngeek_cpt_single_name, 'ngeektheme'), /* Add New Display Title */
				'edit' => __( 'Details', 'ngeektheme' ), /* Edit Dialog */
				'edit_item' => __($ngeek_cpt_single_name . ' Details', 'ngeektheme'), /* Edit Display Title */
				'new_item' => __('New ' . $ngeek_cpt_single_name, 'ngeektheme'), /* New Display Title */
				'view_item' => __('View ' . $ngeek_cpt_single_name, 'ngeektheme'), /* View Display Title */
				'search_items' => __('Search ' . $ngeek_cpt_plural_name, 'ngeektheme'), /* Search Custom Type Title */
				'not_found' =>  __('No ' . $ngeek_cpt_plural_name . ' found', 'ngeektheme'), /* This displays if there are no entries yet */
				'not_found_in_trash' => __('No ' . $ngeek_cpt_plural_name . ' in Trash', 'ngeektheme'), /* This displays if there is nothing in the trash */
				'parent_item_colon' => ''
				), /* end of arrays */
				'description' => __( '', 'ngeektheme' ), /* Custom Type Description */
				'public' => true,
				'publicly_queryable' => true,
				'exclude_from_search' => false,
				'show_ui' => true,
				'query_var' => true,
				'menu_position' => 8, /* this is what order you want it to appear in on the left hand side menu */
				'menu_icon' => 'dashicons-palmtree', /* the icon for the custom post type menu */
				'rewrite'	=> array( 'slug' => $ngeek_cpt_slug_name, 'with_front' => false ), /* you can specify its url slug */
				'has_archive' => $ngeek_cpt_slug_name, /* you can rename the slug here */
				'capability_type' => 'post',
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
				'hierarchical' => false,
				/* the next one is important, it tells what's enabled in the post editor */
				'supports' => false, // Hide these in the admin
		 	)
		);
	}

	/**
	 * Register Region taxonomy
	 *
	 * @access public
	 */
	public function ngeek_region_taxonomy_register() {
		$ngeek_tax_id = 'cl_location_region'; // Slug name
		$ngeek_tax_slug_name = 'cl_location_region'; // Permalink
		$ngeek_tax_menu_name = 'Regions'; // Menu name
		$ngeek_tax_single_name = 'Region'; // Singular name
		$ngeek_tax_plural_name = 'Regions'; // Plural name

		$labels = array(
			'name' => __($ngeek_tax_menu_name, 'ngeektheme'),
			'singular_name' => __($ngeek_tax_single_name, 'ngeektheme'),
			'search_items' =>  __('Search ' . $ngeek_tax_plural_name, 'ngeektheme'),
			'all_items' => __('All ' . $ngeek_tax_plural_name, 'ngeektheme'),
			'parent_item' => __( 'Parent ' . $ngeek_tax_single_name, 'ngeektheme'),
			'parent_item_colon' => __( 'Parent ' . $ngeek_tax_single_name . ':', 'ngeektheme'),
			'edit_item' => __( 'Edit ' . $ngeek_tax_single_name, 'ngeektheme'),
			'update_item' => __( 'Update ' . $ngeek_tax_single_name, 'ngeektheme'),
			'add_new_item' => __( 'Add New ' . $ngeek_tax_single_name, 'ngeektheme'),
			'new_item_name' => __( 'New ' . $ngeek_tax_single_name . ' Name', 'ngeektheme'),
			'menu_name' => __($ngeek_tax_menu_name, 'ngeektheme'),
		);
		register_taxonomy($ngeek_tax_id,array('cl_location'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_in_rest'      => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $ngeek_tax_slug_name),
		));
	}

	/**
	 * Register State taxonomy
	 *
	 * @access public
	 */
	public function ngeek_state_taxonomy_register() {
		$ngeek_tax_id = 'cl_location_state'; // Slug name
		$ngeek_tax_slug_name = 'cl_location_state'; // Permalink
		$ngeek_tax_menu_name = 'States'; // Menu name
		$ngeek_tax_single_name = 'State'; // Singular name
		$ngeek_tax_plural_name = 'States'; // Plural name

		$labels = array(
			'name' => __($ngeek_tax_menu_name, 'ngeektheme'),
			'singular_name' => __($ngeek_tax_single_name, 'ngeektheme'),
			'search_items' =>  __('Search ' . $ngeek_tax_plural_name, 'ngeektheme'),
			'all_items' => __('All ' . $ngeek_tax_plural_name, 'ngeektheme'),
			'parent_item' => __( 'Parent ' . $ngeek_tax_single_name, 'ngeektheme'),
			'parent_item_colon' => __( 'Parent ' . $ngeek_tax_single_name . ':', 'ngeektheme'),
			'edit_item' => __( 'Edit ' . $ngeek_tax_single_name, 'ngeektheme'),
			'update_item' => __( 'Update ' . $ngeek_tax_single_name, 'ngeektheme'),
			'add_new_item' => __( 'Add New ' . $ngeek_tax_single_name, 'ngeektheme'),
			'new_item_name' => __( 'New ' . $ngeek_tax_single_name . ' Name', 'ngeektheme'),
			'menu_name' => __($ngeek_tax_menu_name, 'ngeektheme'),
		);
		register_taxonomy($ngeek_tax_id,array('cl_location'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_in_rest'      => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $ngeek_tax_slug_name),
		));
	}

	/**
	 * Define path for custom admin CSS
	 *
	 * @access public
	 */
	public function locations_receiver_admin_css() {
	    wp_enqueue_style('locations-receiver-css', CLDataAPI::get_instance()->settings->uri . '/css/admin.css');
	}


	/**
	 * Add alert warning users to add Connected Site ID from Remote
	 *
	 * @access public
	 */
	public function cl_locations_site_id_alert() {
		if( empty( get_option( 'cl_location_id' ) ) ) {
		    echo '<div class="notice error my-acf-notice is-dismissible" >
		        <p>Please add a Connected Site ID from the Locations API Remote <a href="/wp-admin/edit.php?post_type=cl_location&page=cl_location-settings">here</a>.</p>
		    </div>';
		}
	}

	/**
	 * Locations Admin Screen
	 */

	/**
	 * Remove "edit" from the bulk actions menu
	 *
	 * @access public
	 * @static
	 * @param array $columns
	 * @return array $columns
	 */
    public static function locations_receiver_bulk_actions( $actions ){
        unset( $actions[ 'edit' ] );
        return $actions;
    }

	/**
	 * Remove the date dropdown filter from the Locations admin
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function remove_date_drop(){

	$screen = get_current_screen();

	    if ( 'cl_location' == $screen->post_type ){
	        add_filter('months_dropdown_results', '__return_empty_array');
	    }
	}

	/**
	 * First create the dropdown
	 * make sure to change POST_TYPE to the name of your custom post type
	 *
	 * @author Ohad Raz
	 *
	 * @return void
	 */
	public function locations_receiver_admin_posts_filter_restrict_manage_posts(){
	    $type = 'post';
	    if (isset($_GET['post_type'])) {
	        $type = $_GET['post_type'];
	    }

	    //only add filter to post type you want
	    if ('cl_location' == $type){

	       	// get list of state values

	       	$meta_values = CLDataAPI_Helper::ngeek_get_meta_values( $key = 'state', $type = 'cl_location' );

	       	$dropdown_values = [];

	       	foreach ( $meta_values as $dropdown_value ) {
	       		$dropdown_values[ $dropdown_value ] = $dropdown_value;
	       	}
/*
	        $values = array(
	            'label' => 'value',
	            'label1' => 'value1',
	            'label2' => 'value2',
	        );
*/
	        ?>
	        <select name="ADMIN_FILTER_FIELD_VALUE">
	        <option value=""><?php _e('All states ', 'wose45436'); ?></option>
	        <?php
	            $current_v = isset($_GET['ADMIN_FILTER_FIELD_VALUE'])? $_GET['ADMIN_FILTER_FIELD_VALUE']:'';
	            foreach ($dropdown_values as $label => $value) {
	                printf
	                    (
	                        '<option value="%s"%s>%s</option>',
	                        $value,
	                        $value == $current_v? ' selected="selected"':'',
	                        $label
	                    );
	                }
	        ?>
	        </select>
	        <?php
	    }
	}

	/**
	 * Filter by post meta if query is submitted
	 *
	 * @author Ohad Raz
	 * @param  (wp_query object) $query
	 *
	 * @return void
	 */
	public static function locations_receiver_posts_filter( $query ){
	    global $pagenow;
	    $type = 'post';
	    if (isset($_GET['post_type'])) {
	        $type = $_GET['post_type'];
	    }
	    if ( 'cl_location' == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && $_GET['ADMIN_FILTER_FIELD_VALUE'] != '') {
	        $query->query_vars['meta_key'] = 'state';
	        $query->query_vars['meta_value'] = $_GET['ADMIN_FILTER_FIELD_VALUE'];
	    }
	}

	/**
	 * Define and order columns for the Locations admin.
	 *
	 * @access public
	 * @static
	 * @param array $columns
	 * @return array $columns
	 */
	public static function locations_receiver_set_admin_column_list($columns) {
		unset( $columns['date']);
		$columns['address'] = 'Address';
		$columns['state'] = 'State';
		$columns['location_id'] = 'Location ID';
		$columns['modified'] = 'Last Modified';
		return $columns;
	}

	/**
	 * Output data for each column.
	 *
	 * @access public
	 * @static
	 * @param string $column
	 * @param integer $post_id
	 */
	public static function locations_receiver_populate_custom_columns( $column, $post_id ) {
		if ($column == 'address') {
			echo get_post_meta( $post_id , 'address_1' , true );
		}
		if ($column == 'state') {
			echo get_post_meta( $post_id , 'state' , true );
		}
		if ($column == 'location_id') {
			echo get_post_meta( $post_id , 'location_id' , true );
		}
		if ($column == 'modified') {
			$m_orig = get_post_field( 'post_modified', $post_id, 'raw' );
		    $m_stamp = strtotime( $m_orig );
		    $modified	= date('n/j/y @ g:i a', $m_stamp );
			echo $modified;
		}
	}

	/**
	 * Designate which columns in the admin are sortable.
	 *
	 * @access public
	 * @static
	 * @param array $columns
	 * @return array $columns
	 */
	public static function locations_receiver_sortable_columns($columns) {
		$columns['modified'] = 'modified';
		$columns['state'] = 'state';
		return $columns;
	}


	/**
	 * Edit the action row buttons for Locations so users can't edit fields from here.
	 *
	 * @access public
	 * @static
	 * @param array $actions
	 * @param object $post
	 * @return array $actions
	 */
	public static function ngeek_edit_action_row( $actions, $post ){
	    //check for your post type
	    if ($post->post_type == 'cl_location'){
		    $can_edit_post = current_user_can( 'edit_post', $post->ID );
			$title = _draft_or_post_title();

		    if ( $can_edit_post && 'trash' != $post->post_status ) {
		        $actions['edit'] = sprintf(
		            '<a href="%s" aria-label="%s">%s</a>',
		            get_edit_post_link( $post->ID ),
		            /* translators: %s: post title */
		            esc_attr( sprintf( __( 'Details &#8220;%s&#8221;' ), $title ) ),
		            __( 'Details' )
		        );
	    		unset( $actions['inline hide-if-no-js'] ); // Quick Edit
			    unset( $actions['trash'] ); // Trash
		    }
	    }
	    return $actions;
	}

	/**
	 * Location Settings Panel
	 */

	/**
	 * Adds a submenu page under a custom post type parent.
	 */
	public function ngeek_register_cllocation_settings_page() {
	    add_submenu_page(
	        'edit.php?post_type=cl_location',
	        __( 'Settings' ),
	        __( 'Settings' ),
	        'manage_options',
	        'cl_location-settings',
	        'CLDataAPI_Admin::ngeek_cllocation_settings_callback'
	    );
	}

	/**
	 * Display callback for the submenu page.
	 */
	public function ngeek_cllocation_settings_callback() {
		$new_query = new WP_Query(array(
			'posts_per_page' 	=> 1,
			'orderby' 			=> 'meta_value_num',
			'order' 			=> 'DESC',
			'meta_key' 			=> 'post_modified_microtime',
			'post_type' 		=> 'cl_location',
		) );
		while ( $new_query->have_posts() ) : $new_query->the_post();
			global $post;
			$last_location_id = get_post_meta($post->ID,'location_id', true);
		endwhile;
		wp_reset_postdata();


	    echo '<div class="wrap">';
	        echo '<h1>Location Settings</h1>';
	        echo '<p>The most current imported timestamp is <span id="reset-date-collect">' . get_option( 'cllocation_modified_date' ) . '</span>';
	        if (!empty($last_location_id)) echo '<br/>The last imported Location is ' . $last_location_id;
	        echo '</p>';
	        echo '<p class="submit">';
				echo '<button id="cllocation-import-action" href="#" name="import-five-locations" class="button-primary">Import Next 5 Locations</button>';
			echo '</p>';
		    echo '<script>jQuery(document).ready(function($) {
			    	$(\'#cllocation-import-action\').click(function(e){
				    	e.preventDefault();
						var data = {
							action: \'import_some_locations\',
						};
						jQuery.post(ajaxurl, data, function(response) {
							$(\'#reset-date-collect\').text(response);
						});
				    });
	   			});</script>';
		    echo '<p>';
	        	echo 'WARNING: This button will reset the API import date back to zero.<br />';
				echo '<button id="reset-date-action" href="#" class="button-primary">Reset Import Date</button>';
			echo '</p>';
		    echo '<script>jQuery(document).ready(function($) {
			    	$(\'#reset-date-action\').click(function(e){
				    	e.preventDefault();
						var c = confirm("Reset Location import date back to the beginning?");
						if ( c ) {
				    		var data = {
								action: \'reset_modified_date\',
							};
							jQuery.post(ajaxurl, data, function(response) {
								$(\'#reset-date-collect\').text(response);
							});
						}
				    });
	   			});</script>';
	   			if (isset($_POST['cl_location_id'])) {
			        update_option('cl_location_id', $_POST['cl_location_id']);
			        $value = $_POST['cl_location_id'];
			    }

				$value = get_option('cl_location_id');
	   			echo '<form method="POST">';
				    echo '<label for="cl_location_id">Site Connection ID from Locations API</label>';
				    echo '<input type="number" name="cl_location_id" id="cl_location_id" min="1" max="999" value="' . $value . '"><br />';
				    echo '<input type="submit" value="Save" class="button button-primary button-large">';
				echo '</form>';
	    echo '</div>';
	}


	/**
	 * Single Location Admin Screen
	 */


	/**
	 * Remove edit boxes from the single Location admin
	 *
	 * @access public
	 * @static
	 */
	public static function ngeek_remove_location_edit_boxes() {
		remove_meta_box( 'slugdiv', 'cl_location', 'normal' );
		remove_meta_box( 'submitdiv', 'cl_location', 'normal' );
	}

	public static function custom_locations_post_page( $post ) {
		$screen = get_current_screen();
		if($screen->post_type=='cl_location' && $screen->id=='cl_location') {
			echo '<h1>'. html_entity_decode( $post->post_title ) . '</h1>';
			echo self::locations_receiver_content_output( $post );
		}
	}

	/**
	 * locations_receiver_content_output function.
	 *
	 * @access public
	 * @static
	 * @param integer $post
	 * @return string $output
	 */
	public static function locations_receiver_content_output( $post ) {
		$output = [];
		$all_fields = get_fields();
		//print_r( $all_fields );
		if ( get_post_field('post_content', $post->ID ) ) {
			$output[] = '<h3>Description</h3>';
			$output[] = apply_filters('the_content', get_post_field('post_content', $post->ID ));
		}
		$output[] = '<h3>Information</h3>';
		if ( get_field('address_1') ) {
			$output[] = '<strong>Address 1</strong>: ' . get_field('address_1');
		}
		if ( get_field('address_2') ) {
			$output[] = '<strong>Address 2</strong>: ' . get_field('address_2');
		}
		if ( get_field('city') ) {
			$output[] = '<strong>City</strong>: ' . get_field('city');
		}
		if ( get_field('state') ) {
			$output[] = '<strong>State</strong>: ' . get_field('state');
		}
		if ( get_field('zip') ) {
			$output[] = '<strong>Zip</strong>: ' . get_field('zip');
		}
		if ( get_field('phone_1') ) {
			$output[] = '<strong>Phone 1</strong>: ' . get_field('phone_1');
		}
		if ( get_field('phone_2') ) {
			$output[] = '<strong>Phone 2</strong>: ' . get_field('phone_2');
		}
		if ( get_field('fax') ) {
			$output[] = '<strong>Fax</strong>: ' . get_field('fax');
		}
		if ( get_field('email') ) {
			$output[] = '<strong>Fax</strong>: ' . get_field('email');
		}
		if ( get_field('website_address') ) {
			$output[] = '<strong>Website address</strong>: ' . get_field('website_address');
		}
		if ( get_field('hours_su') ) {
			$output[] = '<strong>hours_su</strong>: ' . get_field('hours_su');
		}
		if ( get_field('hours_mo') ) {
			$output[] = '<strong>hours_mo</strong>: ' . get_field('hours_mo');
		}
		if ( get_field('hours_tu') ) {
			$output[] = '<strong>hours_tu</strong>: ' . get_field('hours_tu');
		}
		if ( get_field('hours_we') ) {
			$output[] = '<strong>hours_we</strong>: ' . get_field('hours_we');
		}
		if ( get_field('hours_th') ) {
			$output[] = '<strong>hours_th</strong>: ' . get_field('hours_th');
		}
		if ( get_field('hours_fr') ) {
			$output[] = '<strong>hours_fr</strong>: ' . get_field('hours_fr');
		}
		if ( get_field('hours_sa') ) {
			$output[] = '<strong>hours_sa</strong>: ' . get_field('hours_sa');
		}
		if ( get_field('location_photo_1') ) {
			$output[] = '<strong>location_photo_1</strong>: </br>
				<img src="' . get_field('location_photo_1')['sizes']['medium'] . '"/>';
		}
		if ( get_field('location_photo_2') ) {
			$output[] = '<strong>location_photo_2</strong>: </br>
				<img src="' . get_field('location_photo_2')['sizes']['medium'] . '"/>';
		}
		if ( get_field('location_photo_3') ) {
			$output[] = '<strong>location_photo_3</strong>: </br>
				<img src="' . get_field('location_photo_3')['sizes']['medium'] . '"/>';
		}
		if ( get_field('latitude') ) {
			$output[] = '<strong>latitude</strong>: ' . get_field('latitude');
		}
		if ( get_field('longitude') ) {
			$output[] = '<strong>longitude</strong>: ' . get_field('longitude');
		}
		if( have_rows('staff') ) {
			$i = 1;
			while( have_rows('staff') ): the_row();
				$output[] = '<h3>Staff ' . $i . '</h3>';
				if ( get_sub_field('staff_name') ) {
					$output[] = '<strong>staff_name</strong>: ' . get_sub_field('staff_name');
				}
				if ( get_sub_field('staff_title') ) {
					$output[] = '<strong>staff_title</strong>: ' . get_sub_field('staff_title');
				}
				if ( get_sub_field('staff_email') ) {
					$output[] = '<strong>staff_email</strong>: ' . get_sub_field('staff_email');
				}
				if ( get_sub_field('staff_phone') ) {
					$output[] = '<strong>staff_phone</strong>: ' . get_sub_field('staff_phone');
				}
				if ( get_sub_field('staff_bio') ) {
					$output[] = '<strong>staff_bio</strong>: ' . get_sub_field('staff_bio');
				}
				if ( get_sub_field('staff_photo') ) {
					$output[] = '<strong>staff_photo</strong>: </br>
						<img src="' . get_sub_field('staff_photo')['sizes']['medium'] . '"/>';
				}
				$i ++;
			endwhile;
		}
		return nl2br( implode("\n",$output) );
	}
}