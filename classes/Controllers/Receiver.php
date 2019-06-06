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
					'permission_callback' => array( $this, 'authenticate' ),
				),
			)
		);
	}

	public function authenticate() {

		$json_str    = file_get_contents( 'php://input' );
		$source_data = (object) json_decode( $json_str );

		$nonce   = wp_create_nonce( 'data_sync_api' );
		$request = new WP_REST_Request( 'GET', '/' . DATA_SYNC_API_BASE_URL . '/options/secret_key' );
		$request->set_query_params( array( 'nonce' => $nonce ) );
		$response   = rest_do_request( $request );
		$secret_key = $response->get_data();
		$auth       = new Auth();

		return $auth->verify_signature( $source_data, $secret_key );

	}

	public function receive() {

		$json_str    = file_get_contents( 'php://input' );
		$source_data = (object) json_decode( $json_str );

		$this->parse( $source_data );

	}

	private function parse( object $source_data ) {

		print_r( $source_data );

		$source_options   = (object) $source_data->options;
		$connected_sites  = (object) $source_data->connected_sites;
		$receiver_options = (object) Options::receiver()->get_data();
		$receiver_site_id = (int) $source_data->_receiver_site_id;

		PostTypes::add_new_cpts( $source_options );
		if ( $source_options->enable_new_cpts ) {
			PostTypes::save_options();
		}

		foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {
			foreach ( $source_data->posts->$post_type_slug as $post ) {
				$filtered_post = Posts::filter( $post, $receiver_site_id );
				if ( false !== $filtered_post ) {
					Posts::save( $filtered_post );
				}
//				if ( $post_type_slug === 'locations' ) {
//				}
			}
		}

	}

}