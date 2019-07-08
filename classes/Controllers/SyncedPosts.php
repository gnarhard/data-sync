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

	public static function filter( object $post ) {

		$post->synced   = self::is_synced( $post );
		$excluded_sites = unserialize( $post->post_meta->_excluded_sites[0] );

		foreach ( $excluded_sites as $excluded_site_id ) {
			if ( (int) $excluded_site_id === (int) get_option( 'data_sync_receiver_site_id' ) ) {
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

		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . get_option( 'data_sync_receiver_site_id' ) . '/' . $post->ID;
		$url = Helpers::format_url( $url );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			new Logs( 'ERROR: SyncedPosts: ' . $response->get_error_message(), true );
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body )[0];

			return $data;
		}

	}


	public function get( WP_REST_Request $request ) {
		$data = (object) $request->get_url_params();

		$result   = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			)
		);
		$response = new WP_REST_Response( $result );
		$response->set_status( 201 );

		return $response;

	}

	public function get_all() {
		$response = new WP_REST_Response( SyncedPost::get_all() );
		$response->set_status( 201 );

		return $response;
	}

	public static function save( int $receiver_post_id, object $source_post ) {

		// RECEIVER SIDE.
		$data                   = new stdClass();
		$data->source_post_id   = $source_post->ID;
		$data->name             = $source_post->post_title;
		$data->receiver_post_id = $receiver_post_id;
		$data->receiver_site_id = get_option( 'data_sync_receiver_site_id' );

		$auth     = new Auth();
		$json     = $auth->prepare( $data, get_option( 'secret_key' ) );
		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/sync_post' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
		$body     = wp_remote_retrieve_body( $response );
//		print_r( $body );
	}

	public function save_to_sync_table( WP_REST_Request $request ) {

		// SOURCE SIDE.
		$data                 = (object) json_decode( file_get_contents( 'php://input' ) );
		$existing_synced_post = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			)
		);

		if ( count( $existing_synced_post ) ) {
			$data->id = $existing_synced_post[0]->id;
			SyncedPost::update( $data );
		} else {
			SyncedPost::create( $data );
		}

	}

	public function delete( $post_id ) {

		if ( get_option( 'source_site' ) ) {

			$source_data                 = new stdClass();
			$source_data->source_post_id = $post_id;
			$connected_sites             = (array) ConnectedSites::get_all()->get_data();

			$post = get_post( $post_id );

			foreach ( $connected_sites as $site ) {

				new Logs( 'STARTING BULK DELETE FOR ' . $site->url );

				$args        = array(
					'receiver_site_id' => (int) $site->id,
					'source_post_id'   => $post_id,
				);
				$synced_post = SyncedPost::get_where( $args );

				// WordPress TRIES TO DELETE ALL DATA ASSOCIATED WITH THE POST ID, INCLUDING REVISIONS.
				// WE DON'T SYNC REVISIONS SO WE CAN SKIP IF IT ISN'T IN THE SYNCED POST TABLE.
				if ( count( $synced_post ) ) {
					$synced_post = $synced_post[0];

					$source_data->receiver_post_id = $synced_post->receiver_post_id;
					$source_data->debug            = get_option( 'debug' );
					$source_data->receiver_site_id = (int) $site->id;
					$auth                          = new Auth();
					$json                          = $auth->prepare( $source_data, $site->secret_key );
					$url                           = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/delete_receiver_post/';
					$response                      = wp_remote_post( $url, [ 'body' => $json ] );

					if ( is_wp_error( $response ) ) {
						echo $response->get_error_message();
						new Logs( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(), true );
					} else {
						print_r( $response['body'] );

						SyncedPost::delete( $synced_post->id );
					}

					new Logs( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . $site->url );

				}
			}

		} else {


			$post                            = get_post( $post_id );
			$receiver_data                   = new stdClass();
			$receiver_data->receiver_post_id = $post_id;
			$receiver_data->debug            = get_option( 'debug' );
			$receiver_data->receiver_site_id = (int) get_option( 'data_sync_receiver_site_id' );
			$auth                            = new Auth();
			$json                            = $auth->prepare( $receiver_data, get_option( 'secret_key' ) );
			$url                             = trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/delete_synced_post/';
			$response                        = wp_remote_post( $url, [ 'body' => $json ] );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				new Logs( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(), true );
			} else {
				print_r( $response['body'] );
				new Logs( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . get_site_url() );
			}
		}

	}

	public function delete_post() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		new Logs( 'Received delete request for post: ' . wp_json_encode( $data ) );

		return wp_delete_post( $data->receiver_post_id );
	}

	public function delete_synced_post() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		new Logs( 'Received delete request for post: ' . wp_json_encode( $data ) );

		$args        = array(
			'receiver_site_id' => (int) $data->receiver_site_id,
			'receiver_post_id' => $data->receiver_post_id,
		);
		$synced_post = SyncedPost::get_where( $args );

		if ( count( $synced_post ) ) {
			return SyncedPost::delete( $synced_post[0]->id );
		}
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
			'/synced_posts/all',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_all' ),
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/delete_receiver_post/',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'delete_post' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/synced_posts/delete_synced_post/',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'delete_synced_post' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
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
				),
			)
		);
	}

}