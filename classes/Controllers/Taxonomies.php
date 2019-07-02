<?php


namespace DataSync\Controllers;


/**
 * Class Taxonomies
 * @package DataSync\Controllers
 */
class Taxonomies {

	/**
	 * Taxonomies constructor.
	 *
	 * Creates/Updates all terms sent from source.
	 *
	 * @param int $post_id
	 * @param object $taxonomies
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	public static function save( int $post_id, object $taxonomies ) {
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
			if ( false !== $taxonomy_data ) {
				foreach ( $taxonomy_data as $term ) {
					wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
				}
			}
		}
	}

	public function register() {

			/**
			 * Taxonomy: States.
			 */

			$labels = array(
				"name" => __( "States", "twentynineteen" ),
				"singular_name" => __( "State", "twentynineteen" ),
			);

			$args = array(
				"label" => __( "States", "twentynineteen" ),
				"labels" => $labels,
				"public" => true,
				"publicly_queryable" => true,
				"hierarchical" => false,
				"show_ui" => true,
				"show_in_menu" => true,
				"show_in_nav_menus" => true,
				"query_var" => true,
				"rewrite" => array( 'slug' => 'state', 'with_front' => true, ),
				"show_admin_column" => false,
				"show_in_rest" => true,
				"rest_base" => "state",
				"rest_controller_class" => "WP_REST_Terms_Controller",
				"show_in_quick_edit" => false,
			);
			register_taxonomy( "state", array( "locations", "events" ), $args );

	}
}