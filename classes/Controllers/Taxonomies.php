<?php


namespace DataSync\Controllers;


class Taxonomies {

	public function __construct( $post_id, $taxonomies ) {
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
			foreach ( $taxonomy_data as $term ) {
				wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
			}
		}
	}
}