<?php


namespace DataSync\Controllers;


use DataSync\Models\Taxonomy;

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

	public static function get_id_from_slug( string $slug ) {
		$args = [ 'name' => $slug ];

		return Taxonomy::get_where( $args );
	}

	public static function save_to_wp( int $post_id, object $taxonomies ) {
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
			if ( false !== $taxonomy_data ) {
				foreach ( $taxonomy_data as $term ) {
					wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
				}
			}
		}
	}


	static function save( object $data ) {

		$existing_taxonomies = (array) self::get_id_from_slug( $data->name );

		if ( count( $existing_taxonomies ) ) {
			foreach ( $existing_taxonomies as $taxonomy ) {
				$data->id = $taxonomy->id;
				Taxonomy::update( $data );
			}
		} else {
			$new_id = Taxonomy::create( $data );
			if ( is_numeric( $new_id ) ) {
				$data->id = $new_id;
			}
		}

		$new_data[] = $data;

		return wp_parse_args( $new_data );
	}


	public function register() {

		$synced_taxonomies = Taxonomy::get_all();

		foreach ( $synced_taxonomies as $taxonomy ) {
			$args = (array) json_decode( $taxonomy->data );

			foreach ( $args as $key => $value ) {
				if ( ( 'true' === $value ) || ( 'false' === $value ) ) {
					$args[ $key ] = ( 'true' === $value );
				}
			}

			$args['labels'] = array( 'menu_name' => $args['label'] );

			$post_types = $args['object_types'];
			unset( $args['object_types'] );
			unset( $args['id'] );

			$result = register_taxonomy( $taxonomy->name, $post_types, $args );
		}

	}
}