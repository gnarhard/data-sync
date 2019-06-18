<?php


namespace DataSync\Controllers;

use DataSync\Helpers;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SyncedPosts {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_sync_status' ),
				),
			)
		);
		// TODO: still getting rest_no_route.
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/(?P<receiver_site_id>\d+)/(?P<source_post_id>\d+))',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_sync_status' ),
					'args'     => array(
						'source_post_id'   => array(
							'description' => 'Source Post ID',
							'type'        => 'int',
						),
						'receiver_site_id' => array(
							'description' => 'Receiver Site ID',
							'type'        => 'int',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_to_sync_table' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'source_post_id'   => array(
							'description' => 'Source Post ID',
							'type'        => 'int',
						),
						'receiver_site_id' => array(
							'description' => 'Receiver Site ID',
							'type'        => 'int',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_from_sync_table' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'source_post_id'   => array(
							'description' => 'Source Post ID',
							'type'        => 'int',
						),
						'receiver_site_id' => array(
							'description' => 'Receiver Site ID',
							'type'        => 'int',
						),
					),
				),
			)
		);
	}

	public static function filter( object $post, int $receiver_site_id ) {

		$post->synced   = self::is_synced( $post, $receiver_site_id );
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

		$source_post_id = $post->ID;
		$post_array     = (array) $post; // must convert to array to use wp_insert_post.


		// MUST UNSET ID TO INSERT. PROVIDE ID TO UPDATE

		unset( $post_array['ID'] );
		if ( $post->synced ) {
			// TODO: get post ID from sync table
		}
		unset( $post_array['post_meta'] );
		unset( $post_array['taxonomies'] );
		unset( $post_array['media'] );
		unset( $post_array['guid'] );

		foreach ( $post_array as $key => $value ) {
			$post_array[ $key ] = str_replace( $post_array['source_url'], get_site_url(), $value );
		}

		print_r( $post_array );

		die();

		$receiver_post_id = wp_insert_post( $post_array );

		if ( $receiver_post_id ) {

			foreach ( $post_meta as $meta_key => $meta_value ) {
				// Yoast and ACF data will be in here.
				update_post_meta( $receiver_post_id, $meta_key, $meta_value );
			}

			new Taxonomies( $receiver_post_id, $post->taxonomies );
			new Media( $receiver_post_id, $post->media );

		}

		var_dump( $post_id );
		die();

		Posts::save_to_sync_table( $post_id, $site_id );
	}

	public static function is_synced( object $post, int $receiver_site_id ) {
		print_r($post);
		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . $receiver_site_id . '/' . $post->ID;
		$url = Helpers::format_url( $url );
		echo $url;
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			// TODO: HANDLE THIS MORE GRACEFULLY.
		}
		$body = wp_remote_retrieve_body( $response );
		$auth = new Auth();
//		$source_data->sig               = (string) $auth->create_signature( $json_decoded_data, $site->secret_key );
//		$auth->verify_signature( $body, $key );
		print_r( $body );
		die();


	}

	public function get_sync_status( WP_REST_Request $request ) {
		$data             = $request->get_url_params();
		$source_post_id   = $data['source_post_id'];
		$receiver_site_id = $data['receiver_site_id'];

		print_r( $data );
		die();
		$return = Post::get( $source_post_id, $receiver_site_id );

		$response = new WP_REST_Response();
		$response->set_status( 201 );

		// TODO: ADD SIG
		return $response;

	}

	public function save_to_sync_table( WP_REST_Request $request ) {
		// TODO: send to source to save in wp_data_sync_posts
	}

	public function delete_from_sync_table( WP_REST_Request $request ) {

	}

}