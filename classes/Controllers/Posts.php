<?php


namespace DataSync\Controllers;

use WP_Query;
use stdClass;

class Posts {

	public $table_name = 'data_sync_post_types';

	public $view_namespace = 'DataSync';

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ] );
		require_once DATA_SYNC_PATH . 'views/admin/post/meta-boxes.php';
	}

	public function add_meta_boxes() {
		$push_enabled_post_types = get_option( 'push_enabled_post_types' );

		add_meta_box(
			'canonical_site',
			__(
				'Canonical Site',
				'textdomain'
			),
			$this->view_namespace . '\add_canonical_radio_inputs',
			$push_enabled_post_types
		);

		add_meta_box(
			'excluded_sites',
			__(
				'Sites Excluded From Sync',
				'textdomain'
			),
			$this->view_namespace . '\add_excluded_sites_select_field',
			$push_enabled_post_types
		);
	}

	public function save_meta_boxes( $post_id ) {
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['data_sync_post_meta_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = wp_unslash( $_POST['data_sync_post_meta_box_nonce'] );

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'data_sync_post_meta_box' ) ) {
			return $post_id;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		/* OK, it's safe for us to save the data now. */
		if ( isset( $_POST['canonical_site'] ) ) {
			$data = sanitize_text_field( wp_unslash( $_POST['canonical_site'] ) );
			update_post_meta( $post_id, '_canonical_site', $data );
		}
		if ( isset( $_POST['excluded_sites'] ) ) {
			$data           = $_POST['excluded_sites'];
			$sanitized_data = array_map( 'absint', $data );
			update_post_meta( $post_id, '_excluded_sites', $sanitized_data );
		}

		return true;
	}


	public static function get( $types ) {
		$posts = new stdClass();

		foreach ( $types as $type ) {

			$posts->$type = self::get_posts( $type );

			foreach ( $posts->$type as $post ) {

				$post->source_url = get_site_url();
				$post->post_meta  = get_post_meta( $post->ID );
				$post->taxonomies = array();

				foreach ( get_taxonomies() as $taxonomy ) {
					$post->taxonomies[ $taxonomy ] = get_the_terms( $post->ID, $taxonomy );
				}

				$post->media = self::get_media( $post->ID );


			}
		}

		return $posts;

	}

	private static function get_posts( $type ) {
		$args = array(
			'post_type'      => $type,
			'post_status'    => array( 'publish' ),
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => - 1, // show all posts.
		);

		$loop = new WP_Query( $args );

		return $loop->posts;
	}

	private static function get_media( $post_id ) {
		$media        = new stdClass();
		$media->image = get_attached_media( 'image', $post_id );
		$media->audio = get_attached_media( 'audio', $post_id );
		$media->video = get_attached_media( 'video', $post_id );

		return $media;
	}

	public static function get_acf_fields() {
		$args = array(
			'post_type'      => 'acf-field-group',
			'post_status'    => array( 'publish' ),
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => - 1, // show all posts.
		);

		$loop = new WP_Query( $args );

		$acf_groups = $loop->posts;

		$field_group = array();

		foreach ( $acf_groups as $field ) {

			$key = $field->post_name;

			// load field group.
			$field_group = acf_get_field_group( $key );

			// validate field group.
			if ( empty( $field_group ) ) {
				continue;
			}

			// load fields.
			$field_group['fields'] = acf_get_fields( $field_group );

			// prepare for export.
			$field_group = acf_prepare_field_group_for_export( $field_group );

			// add to json array.
			$json[] = $field_group;

		}

		return $json;

	}

	public static function filter( object $post, int $receiver_site_id ) {
		$excluded_sites = unserialize( $post->post_meta->_excluded_sites[0] );
		foreach ( $excluded_sites as $excluded_site_id ) {
			if ( (int) $excluded_site_id === (int) $receiver_site_id ) {
				return false;
			} else {
				return $post;
			}
		}
	}

	public static function save( object $post ) {


		print_r( $post );

		$post_id    = $post->ID;
		$post_meta  = $post->post_meta;
		$taxonomies = $post->taxonomies;
		$media      = $post->media;


		print_r( $post_meta );
		print_r( $taxonomies );
		die();
		$post_array = (array) $post; // must convert to array to use wp_insert_post.
		// MUST UNSET ID TO INSERT. PROVIDE ID TO UPDATE
		unset( $post_array['ID'] );
		unset( $post_array['post_meta'] );
		unset( $post_array['taxonomies'] );
		unset( $post_array['media'] );
		unset( $post_array['guid'] );

		foreach ( $post_array as $key => $value ) {
			$post_array[ $key ] = str_replace( $post_array['source_url'], get_site_url(), $value );
		}
		$post_id = wp_insert_post( $post_array );

		if ( $post_id ) {

			foreach ( $post_meta as $meta_key => $meta_value ) {
				// Yoast and ACF data will be in here.
				update_post_meta( $post_id, $meta_key, $meta_value );
			}

			foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
				foreach ( $taxonomy_data as $term ) {
					wp_set_object_terms( $post_id, $term->slug, $taxonomy_slug );
				}
			}

		}

		var_dump( $post_id );
		die();

		Posts::save_to_sync_table( $post_id, $site_id );
	}

	private static function save_to_sync_table() {
		// TODO: send to source to save in wp_data_sync_posts
	}


}