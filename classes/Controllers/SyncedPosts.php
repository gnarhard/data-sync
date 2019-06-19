<?php


namespace DataSync\Controllers;

use DataSync\Models\SyncedPost;
use DataSync\Helpers;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use stdClass;

class SyncedPosts {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/sync_post/',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_to_sync_table' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);

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

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/(?P<receiver_site_id>\d+)/(?P<source_post_id>\d+)',
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
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_from_sync_table' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
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
//		print_r( $post );
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

		// Don't change URLs of media that needs to be migrated.
		if ( $post->post_type !== 'attachment' ) {
			unset( $post_array['guid'] );
			foreach ( $post_array as $key => $value ) {
				$post_array[ $key ] = str_replace( $post_array['source_url'], get_site_url(), $value );
			}
		}

		$receiver_post_id = wp_insert_post( $post_array );

		if ( $receiver_post_id ) {

			foreach ( $post->post_meta as $meta_key => $meta_value ) {
				// Yoast and ACF data will be in here.
				update_post_meta( $receiver_post_id, $meta_key, $meta_value );
			}

			new Taxonomies( $receiver_post_id, $post->taxonomies );
			new Media( $receiver_post_id, $post->media, $post->source_url );

			return $receiver_post_id;
		}

	}

	public static function is_synced( object $post, int $receiver_site_id ) {

		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . $receiver_site_id . '/' . $post->ID;
		$url = Helpers::format_url( $url );

		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			// TODO: HANDLE THIS MORE GRACEFULLY.
		} else {
			$body = (int) wp_remote_retrieve_body( $response );
			if ( $body ) {
				return true;
			} else {
				return false;
			}
		}

	}


	public function get_sync_status( WP_REST_Request $request ) {
		$data             = $request->get_url_params();
		$source_post_id   = (int) filter_var( $data['source_post_id'], FILTER_SANITIZE_NUMBER_INT );
		$receiver_site_id = (int) filter_var( $data['receiver_site_id'], FILTER_SANITIZE_NUMBER_INT );

		$return = SyncedPost::get( $source_post_id, $receiver_site_id );
		if ( count( $return ) ) {
			return 1;
		} else {
			return 0;
		}

	}

	public static function sync( int $receiver_post_id, int $receiver_site_id, int $source_post_id, $source_url ) {

		// RECEIVER SIDE
		$data                   = new stdClass();
		$data->source_post_id   = $source_post_id;
		$data->receiver_post_id = $receiver_post_id;
		$data->receiver_site_id = $receiver_site_id;

		$auth     = new Auth();
		$json     = $auth->prepare( $data, get_option( 'secret_key' ) );
		$url      = Helpers::format_url( trailingslashit( $source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/sync_post' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
		$body     = wp_remote_retrieve_body( $response );
		print_r($body);
	}

	public function save_to_sync_table( WP_REST_Request $request ) {

		// SOURCE SIDE
		$json_str = file_get_contents( 'php://input' );
		$data     = (object) json_decode( $json_str );
		echo "\n"; echo 'source'; echo "\n";
		print_r( $data );
		die();
	}

	public function delete_from_sync_table( WP_REST_Request $request ) {

	}

}