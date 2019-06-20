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
	public function __construct( int $post_id, object $taxonomies ) {
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
			if ( false !== $taxonomy_data ) {
				foreach ( $taxonomy_data as $term ) {
					wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
				}
			}
		}
	}
}