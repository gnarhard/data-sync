<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use WP_REST_Server;
use ACF_Admin_Tool_Export;

class SourceData {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/source_data/push',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'push' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				)
			)
		);
	}

	public function push() {
		$source_data     = $this->consolidate();
		$connected_sites = $source_data['source']['connected_sites'];

		foreach ( $connected_sites as $site ) {
			$auth                    = new Auth();
			$auth_response           = $auth->authenticate_site( $site->url );
			$authorization_validated = $auth->validate( $site->url, $auth_response );

			if ( $authorization_validated ) {
				$token    = json_decode( $auth_response )->token;
				$url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
				$args     = array(
					'body'    => $source_data,
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				);
				$response = wp_remote_post( $url, $args );
				print_r( $response['body'] );
			}

		}

	}

	private function consolidate() {

		$options = Options::get_all_source()->data;

		return array(
			'source' => array(
				'options'         => $options,
				'connected_sites' => ConnectedSites::get_all()->data,
				'nonce'           => wp_create_nonce( 'data_push' ),
			),
			'posts'  => Posts::get( array_keys( $options['push_enabled_post_types'] ) ),
			'acf'    => Posts::get_acf_fields(), // use acf_add_local_field_group() to install this array.
		);


	}

}