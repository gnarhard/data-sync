<?php


namespace DataSync\Controllers;


use DataSync\Models\SyncedTaxonomy;

/**
 * Class SyncedTaxonomies
 * @package DataSync\Controllers
 */
class SyncedTaxonomies {

	/**
	 * SyncedTaxonomies constructor.
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

		return SyncedTaxonomy::get_where( $args );
	}


	static function save( object $data ) {

		$existing_taxonomies = (array) self::get_id_from_slug( $data->name );

		if ( count( $existing_taxonomies ) ) {
			foreach ( $existing_taxonomies as $taxonomy ) {
				$data->id = $taxonomy->id;
				SyncedTaxonomy::update( $data );
			}
		} else {
			$new_id = SyncedTaxonomy::create( $data );
			if ( is_numeric( $new_id ) ) {
				$data->id = $new_id;
			}
		}

		$new_data[] = $data;

		return wp_parse_args( $new_data );
	}


	public function register() {

		$synced_taxonomies = SyncedTaxonomy::get_all();

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


	public function delete() {
		// TODO: BUILD THIS
	}
}