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
		add_action( 'delete_post', [ $this, 'delete' ], 10 );
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
					'callback' => array( $this, 'get' ),
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/(?P<receiver_site_id>\d+)/(?P<source_post_id>\d+)',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get' ),
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
				)
			)
		);
	}

	public static function filter( object $post, int $receiver_site_id ) {

		$post->receiver_site_id = $receiver_site_id;
		$post->synced           = self::is_synced( $post );
		$excluded_sites         = unserialize( $post->post_meta->_excluded_sites[0] );

		foreach ( $excluded_sites as $excluded_site_id ) {
			if ( (int) $excluded_site_id === (int) $receiver_site_id ) {
				return false;
			} else {
				return $post;
			}
		}
	}


	public static function is_synced( object $post ) {
		$data = self::get_synced_post_data( $post );

		return isset( $data->id );
	}


	public static function get_synced_post_data( object $post ) {

		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . $post->receiver_site_id . '/' . $post->ID;
		$url = Helpers::format_url( $url );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			// TODO: HANDLE THIS MORE GRACEFULLY.
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body )[0];

			return $data;
		}

	}


	public function get( WP_REST_Request $request ) {
		$data = $request->get_url_params();

		$result = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data['source_post_id'], FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data['receiver_site_id'], FILTER_SANITIZE_NUMBER_INT ),
			)
		);
		$response = new WP_REST_Response( $result );
		$response->set_status( 201 );

		return $response;

	}

	public static function save( int $receiver_post_id, int $receiver_site_id, object $source_post, $source_url ) {

		// RECEIVER SIDE.
		$data                   = new stdClass();
		$data->source_post_id   = $source_post->ID;
		$data->name             = $source_post->post_title;
		$data->receiver_post_id = $receiver_post_id;
		$data->receiver_site_id = $receiver_site_id;

		$auth     = new Auth();
		$json     = $auth->prepare( $data, get_option( 'secret_key' ) );
		$url      = Helpers::format_url( trailingslashit( $source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/sync_post' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
		$body     = wp_remote_retrieve_body( $response );
		print_r( $body );
	}

	public function save_to_sync_table( WP_REST_Request $request ) {

		// SOURCE SIDE.
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		print_r( $data );
		$existing_synced_post = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data['source_post_id'], FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data['receiver_site_id'], FILTER_SANITIZE_NUMBER_INT ),
			)
		);

		if ( count( $existing_synced_post ) ) {
			$data->id = $existing_synced_post[0]->id;
			SyncedPost::update( $data );
		} else {
			SyncedPost::create( $data );
		}

	}

	public function delete( $pid ) {

		if ( get_option( 'source_site' ) ) {
			// todo: delete all receiver site post data with this command
		} else {

			$synced_post = SyncedPost::get_where(
				array(
					'receiver_post_id' => $pid,
					'receiver_site_id' => (int) get_option( 'receiver_site_id' ),
				)
			);

			print_r($synced_post);

		}

	}

}