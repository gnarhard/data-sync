<?php


namespace DataSync\Controllers;


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
					'permission_callback' => array( 'Auth', 'authorize' ),
				),
			)
		);
	}

	public function receive() {

		$json_str    = file_get_contents( 'php://input' );
		$source_data = (object) json_decode( $json_str );

		$this->parse( $source_data );

	}

	private function parse( object $source_data ) {

		$source_options   = (object) $source_data->options;
		$connected_sites  = (object) $source_data->connected_sites;
		$receiver_options = (object) Options::receiver()->get_data();
		$receiver_site_id = (int) $source_data->_receiver_site_id;

		print_r($source_data);die();

		PostTypes::create( $source_options );
		if ( $source_options->enable_new_cpts ) {
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
						$receiver_post_id = SyncedPosts::save( $filtered_post );
						SyncedPosts::sync( $receiver_post_id, $receiver_site_id, $filtered_post->ID, $source_data->url );
					}
				}
			}

		}

	}

}