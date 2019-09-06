<?php


namespace DataSync\Controllers;


use DataSync\Models\SyncedTerm;

/**
 * Class SyncedTaxonomies
 * @package DataSync\Controllers
 */
class SyncedTerms {

	/**
	 * SyncedTaxonomies constructor.
	 *
	 * Creates/Updates all terms sent from source.
	 *
	 * @param int $post_id
	 * @param object $taxonomies
	 */
	public function __construct() {
		// NOTHING RIGHT NOW.
	}

	static function save( object $source_data ) {

		$prepared_data = self::prep( $source_data );

		if ( isset( $data->id ) ) {
			SyncedTerm::update( $prepared_data );
		} else {
			$new_id = SyncedTerm::create( $prepared_data );
			if ( is_numeric( $new_id ) ) {
				$prepared_data->id = $new_id;
			}
		}

		return wp_parse_args( $prepared_data );
	}


	static function prep( $new_data ) {

		$existing_receiver_term = get_term_by( 'slug', $new_data->slug, $new_data->taxonomy );
		$existing_synced_term   = SyncedTerm::get_where( [ 'source_term_id' => $new_data->term_id ] );

		echo 'existing receiver term';
		print_r( $existing_receiver_term );
		echo 'existing synced term';
		print_r( $existing_synced_term );

		$data                   = new \stdClass();
		$data->slug             = $new_data->slug;
		$data->receiver_site_id = get_option( 'data_sync_receiver_site_id' );
		$data->receiver_term_id = $existing_receiver_term->term_id;
		$data->source_term_id   = $new_data->term_id;
		$data->source_parent_id = $new_data->parent;
		$data->diverged         = false; // TODO: ADDRESS THIS IN FUTURE.

		if ( ! empty( $existing_synced_term ) ) {
			$data->id = $existing_synced_term[0]->receiver_term_id;
		}

		return $data;

	}


	public static function save_to_wp( int $post_id, object $taxonomies ) {
		$current_terms = wp_get_post_terms( $post_id );
//		echo 'taxos';
//		print_r( $taxonomies );
//		echo 'current terms';
//		print_r( $current_terms );

		foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
			if ( false !== $taxonomy_data ) {
				foreach ( $taxonomy_data as $term ) {

					$new_term = wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
					print_r( $new_term );

					if ( ! is_wp_error( $new_term ) ) {

						$new_synced_term = SyncedTerms::save( $term );
						echo 'new synced term';
						print_r( $new_synced_term );

					}
				}
			}
		}

		$synced_terms = SyncedTerm::get_all();

		echo 'synced terms';
		print_r( $synced_terms );

		foreach ( $synced_terms as $synced_term ) {

			$receiver_term = get_term( $synced_term->receiver_term_id );

			if ( 0 !== (int) $synced_term->source_parent_id ) {
				$parent_receiver_term = get_term( $synced_term->source_parent_id );

				echo 'receiver term';
				print_r( $receiver_term );

				echo 'parent term';
				print_r( $parent_receiver_term );

				$args = array(
					'parent' => $parent_receiver_term->term_id,
				);

				wp_update_term( $synced_term->receiver_term_id, $receiver_term->taxonomy, $args );
			}

		}
		die();

	}


	public function delete() {
		// TODO: BUILD THIS
	}
}