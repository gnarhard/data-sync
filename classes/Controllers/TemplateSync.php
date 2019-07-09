<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;
use WP_REST_Server;

class TemplateSync {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function push() {


	}

	public function sync() {
		echo 'syncing';


//		$result = File::copy( $source_url, $receiver_url );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/templates/sync',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'sync' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				// TODO: MAKE SURE AUTHORIZE WORKS
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'push' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

}