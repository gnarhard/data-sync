<?php


namespace DataSync\Controllers;

use WP_Query;
use stdClass;
use WP_REST_Server;
use WP_REST_Request;

class Posts {

	public $view_namespace = 'DataSync';

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ] );
		require_once DATA_SYNC_PATH . 'views/admin/post/meta-boxes.php';
		add_filter( 'cptui_pre_register_post_type', [ $this, 'add_meta_boxes_into_cpts' ], 1 );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/post_meta/(?P<id>\d+)',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_custom_post_meta' ),
					'args'     => array(
						'id' => array(
							'description'       => 'ID of post',
							'type'              => 'int',
							'validate_callback' => 'is_numeric',
						),
					),
				),
			)
		);
	}

	public function add_meta_boxes_into_cpts( $args ) {
		$args['register_meta_box_cb'] = [ $this, 'add_meta_boxes' ];

		return $args;
	}

	public function add_meta_boxes() {

		$registered_post_types = get_post_types( array( 'public' => true ), 'names', 'and' );

		add_meta_box(
			'override_post_yoast',
			__(
				'Override Receiver Yoast Settings',
				'textdomain'
			),
			$this->view_namespace . '\add_override_post_yoast_checkbox',
			$registered_post_types,
			'side',
		);

		add_meta_box(
			'canonical_site',
			__(
				'Canonical Site',
				'textdomain'
			),
			$this->view_namespace . '\add_canonical_radio_inputs',
			$registered_post_types,
			'side',
		);

		add_meta_box(
			'excluded_sites',
			__(
				'Sites Excluded From Sync',
				'textdomain'
			),
			$this->view_namespace . '\add_excluded_sites_select_field',
			$registered_post_types,
			'side',
		);
	}

	public function save_meta_boxes( int $post_id ) {
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
		if ( isset( $_POST['override_post_yoast'] ) ) {
			update_post_meta( $post_id, '_override_post_yoast', $_POST['override_post_yoast'] );
		} else {
			update_post_meta( $post_id, '_override_post_yoast', 0 );
		}

		return true;
	}

	public static function get_all( array $types ) {
		$posts = new stdClass();

		foreach ( $types as $type ) {

			// Must convert single string to array.
			$posts->$type = self::get_wp_posts( [ $type ] );

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

	public static function get_single( int $id ) {

		$post             = get_post( $id );
		$post->source_url = get_site_url();
		$post->post_meta  = get_post_meta( $post->ID );
		$post->taxonomies = array();

		foreach ( get_taxonomies() as $taxonomy ) {
			$post->taxonomies[ $taxonomy ] = get_the_terms( $post->ID, $taxonomy );
		}

		$post->media = self::get_media( $post->ID );

		return $post;
	}

	public static function get_wp_posts( array $type ) {

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

	private static function get_media( int $post_id ) {
		$media        = new stdClass();
		$media->image = get_attached_media( 'image', $post_id );
		$media->audio = get_attached_media( 'audio', $post_id );
		$media->video = get_attached_media( 'video', $post_id );

		return $media;
	}


	public function get_custom_post_meta( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );

		$postmeta = get_post_meta( $post_id );

		foreach ( $postmeta as $key => $meta ) {
			$postmeta[ $key ] = $meta[0];
		}

		$postmeta['_excluded_sites'] = unserialize( $postmeta['_excluded_sites'] );

		return $postmeta;
	}


	public static function save( object $post, array $synced_posts ) {

		$post_array = (array) $post; // must convert to array to use wp_insert_post.

		// MUST UNSET ID TO INSERT. PROVIDE ID TO UPDATE.
		unset( $post_array['ID'] );

		if ( $post->synced ) {
			$post_array['ID'] = SyncedPosts::get_receiver_post_id( $post, $synced_posts );
		}

		unset( $post_array['post_meta'] );
		unset( $post_array['taxonomies'] );
		unset( $post_array['media'] );

		// Don't change URLs of media that needs to be migrated.
		if ( 'attachment' !== $post->post_type ) {
			unset( $post_array['guid'] );
			foreach ( $post_array as $key => $value ) {
				$post_array[ $key ] = str_replace( $post_array['source_url'], get_site_url(), $value );
			}
		}

		$receiver_post_id = wp_insert_post( $post_array );

		if ( is_wp_error( $receiver_post_id ) ) {
			$log = new Logs( $receiver_post_id->get_error_message(), true );
			unset( $log );

			return false;
		} elseif ( $receiver_post_id ) {

			$receiver_post_id = (int) $receiver_post_id;

			if ( 'attachment' !== $post->post_type ) {

				$override_post_yoast = (bool) $post->post_meta->_override_post_yoast[0];

				// Yoast and ACF data will be in here.
				foreach ( $post->post_meta as $meta_key => $meta_value ) {

					// IF POST-LEVEL SETTING DOES NOT ALLOW OVERWRITING OF YOAST DATA, UNSET YOAST-RELATED POSTMETA.
					if ( ( ! $override_post_yoast ) && ( false !== strpos( $meta_key, 'yoast' ) ) ) {
						unset( $post->post_meta->$meta_key );
					}

					foreach ( $meta_value as $value ) {
						$updated = update_post_meta( $receiver_post_id, $meta_key, $value );
					}
				}

			}

			Taxonomies::save_to_wp( $receiver_post_id, $post->taxonomies );

			return $receiver_post_id;
		}

	}


}