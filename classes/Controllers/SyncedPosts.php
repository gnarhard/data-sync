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

	public static function filter( object $post, $source_options, $synced_posts ) {

		// TODO: SEND FLAG TO SOURCE IF RECEIVER POST WAS UPDATED MORE RECENTLY THAN THE LAST SOURCE POST WAS UPDATED.

		$post->synced   = self::is_synced( $post, $synced_posts );
		$excluded_sites = unserialize( $post->post_meta->_excluded_sites[0] );

		foreach ( $excluded_sites as $excluded_site_id ) {
			if ( (int) $excluded_site_id === (int) get_option( 'data_sync_receiver_site_id' ) ) {
				return false;
			} else {

				if ( true !== (bool) $source_options->overwrite_yoast ) {

					if ( $post->synced ) {
						// IF SOURCE IS NOT ALLOWED TO OVERWRITE YOAST SETTINGS,
						// AND THE POST IS ALREADY SYNCED,
						// THEN DELETE ALL YOAST POST META DATA.
						$post_meta = (array) $post->post_meta;

						foreach ( $post_meta as $key => $value ) {
							if ( strpos( $key, 'yoast' ) ) {
								unset( $post_meta[ $key ] );
							}
						}

						$post->post_meta = (object) $post_meta;

					}

					// IF SOURCE IS NOT ALLOWED TO OVERWRITE YOAST SETTINGS,
					// AND THE POST ISN'T SYNCED,
					// THEN INCLUDE ALL YOAST POST META DATA.
				}

				return $post;
			}
		}
	}


	public static function is_synced( object $post, array $synced_posts ) {

		if ( self::get_receiver_post_id( $post, $synced_posts ) ) {
			return true;
		}

		return false;

//		$data = self::get_synced_post_data( $post );
//
//		return isset( $data->id );
	}

	public static function retrieve_from_receiver( $data_sync_start_time ) {

		// TODO: ALL ENTRIES AFTER $data_sync_start_time

		$connected_sites = (array) ConnectedSites::get_all()->get_data();
		$all_data        = array();

		foreach ( $connected_sites as $site ) {
			$url = Helpers::format_url( trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/all' );
			$args       = array(
				'body' => [ 'datetime' => $data_sync_start_time ],
			);
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in SyncedPosts::retrieve_from_receiver received from ' . get_site_url() . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
				print_r( wp_remote_retrieve_body( $response ) );
			}

			$all_data[] = json_decode( wp_remote_retrieve_body( $response ) );
		}

		return $all_data;

	}

	public static function get_receiver_post_id( $post, $synced_posts ) {
		foreach ( $synced_posts as $synced_post ) {
			if ( (int) $post->ID === (int) $synced_post->source_post_id ) {
				return $synced_post->receiver_post_id;
			}
		}

		return false;
	}


	public static function get_synced_post_data( object $post ) {

		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . get_option( 'data_sync_receiver_site_id' ) . '/' . $post->ID;
		$url = Helpers::format_url( $url );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$log = new Logs( 'SyncedPosts: ' . $response->get_error_message(), true );
			unset( $log );

			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) )->data;

	}


	public function get( WP_REST_Request $request ) {
		$data = (object) $request->get_url_params();

		$result = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			)
		);

		wp_send_json_success( $result );
	}

	public function get_all() {
		$response = new WP_REST_Response( SyncedPost::get_all() );
		$response->set_status( 201 );

		return $response;
	}

	public function get_after_date( WP_REST_Request $request ) {
		if ( $request ) {
			$datetime = $request->get_param( 'datetime' );
			if ( $datetime ) {
				return SyncedPost::get_all_and_sort( [ 'datetime' => 'DESC' ], $datetime );
			}
		} else {
			return array();
		}
	}

	public static function save_to_source( int $receiver_post_id, object $source_post ) {

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

		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			$log = new Logs( 'Error in SyncedPosts->save() received from ' . get_option( 'data_sync_source_site_url' ) . '. ' . $response->get_error_message(), true );
			unset( $log );
		} else {
			print_r( wp_remote_retrieve_body( $response ) );
		}
	}

	public static function save_to_receiver( int $receiver_post_id, object $source_post ) {
		$data                   = new stdClass();
		$data->source_post_id   = $source_post->ID;
		$data->name             = $source_post->post_title;
		$data->receiver_post_id = $receiver_post_id;
		$data->receiver_site_id = get_option( 'data_sync_receiver_site_id' );

		return self::save( $data );

	}

	public function save_to_sync_table( WP_REST_Request $request ) {

		// SOURCE SIDE.
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		self::save( $data );

		$response = new WP_REST_Response( $data );
		$response->set_status( 201 );

		return $response;

	}

	public static function save( $data ) {
		$existing_synced_post = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			)
		);

//		print_r($existing_synced_post);die();

		if ( count( $existing_synced_post ) ) {
			$data->id = $existing_synced_post[0]->id;

			return SyncedPost::update( $data );
		} else {
			return SyncedPost::create( $data );
		}
	}

	public static function save_all_to_source( array $receiver_synced_posts ) {
		foreach ( $receiver_synced_posts as $site_synced_posts ) {
			foreach ( $site_synced_posts as $synced_post ) {
				$result = self::save( $synced_post );
			}
		}
	}

	public function delete( $post_id ) {

		if ( get_option( 'source_site' ) ) {

			$source_data                 = new stdClass();
			$source_data->source_post_id = $post_id;
			$connected_sites             = (array) ConnectedSites::get_all()->get_data();

			$post = get_post( $post_id );

			foreach ( $connected_sites as $site ) {

				$log = new Logs( 'STARTING BULK DELETE FOR ' . $site->url );
				unset( $log );

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
						$log = new Logs( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(), true );
						unset( $log );
					} else {
						print_r( $response['body'] );

						SyncedPost::delete( $synced_post->id );
					}

					$log = new Logs( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . $site->url );
					unset( $log );

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
				$log = new Logs( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(), true );
				unset( $log );
			} else {
				print_r( $response['body'] );
				$log = new Logs( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . get_site_url() );
				unset( $log );
			}
		}

	}

	public function delete_post() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$log  = new Logs( 'Received delete request for post: ' . wp_json_encode( $data ) );
		unset( $log );

		return wp_delete_post( $data->receiver_post_id );
	}

	public function delete_synced_post() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$log  = new Logs( 'Received delete request for post: ' . wp_json_encode( $data ) );
		unset( $log );

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
			'/synced_posts/retrieve_from_receiver',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_after_date' ),
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