<?php


namespace DataSync\Routes;

use WP_REST_Server;

/**
 * Class PostTypes
 * @package DataSync\Controllers
 */
class PostTypesRoutes {

	const AUTH = 'DataSync\Controllers\Auth';

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}

	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/post_types/check', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this->controller_class, 'get_enabled_post_types' ),
				),
			) );
	}
}
