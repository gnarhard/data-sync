<?php


namespace DataSync\Routes;

use WP_REST_Server;

/**
 * Class Media
 * @package DataSync\Controllers
 */
class MediaRoutes {

	const AUTH = 'DataSync\Controllers\Auth';

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}

	/**
	 *
	 */
	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/media/update', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this->controller_class, 'update' ),
				'permission_callback' => array( $this::AUTH, 'authorize' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/media/sync', array(
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this->controller_class, 'sync' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/media/prep', array(
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this->controller_class, 'prep' ),
			),
		) );
	}
}
