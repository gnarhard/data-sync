<?php


namespace DataSync\Controllers;


use DataSync\Models\SyncedPost;
use WP_REST_Request;
use WP_REST_Server;

class Receiver {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/receive',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'receive' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

	public function receive() {
		$source_data = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->parse( $source_data );
	}

	private function parse( object $source_data ) {

		$receiver_options = (object) Options::receiver()->get_data();
		$receiver_site_id = (int) $source_data->receiver_site_id;
		update_option( 'receiver_site_id', $receiver_site_id );
		update_option( 'source_site_url', $source_data->url );

		$synced_post = SyncedPost::get_where(
			array(
				'receiver_post_id' => 213,
				'receiver_site_id' => (int) get_option( 'receiver_site_id' ),
			)
		);

		print_r($synced_post);die();

		PostTypes::create( $source_data->options );
		if ( $source_data->options->enable_new_cpts ) {
			PostTypes::save_options();
		}

		foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {

			$post_count = count( $source_data->posts->$post_type_slug );

			if ( $post_count === 0 ) {
				// TODO: ERROR MESSAGE ABOUT NO POSTS TO TRANSFER
				echo 'no posts ';
			} else {
				foreach ( $source_data->posts->$post_type_slug as $post ) {
					$filtered_post = SyncedPosts::filter( $post, $receiver_site_id );

					if ( false !== $filtered_post ) {
						$receiver_post_id = Posts::save( $filtered_post );
						SyncedPosts::save( $receiver_post_id, $receiver_site_id, $filtered_post, $source_data->url );
					}
				}
			}

		}

	}

}