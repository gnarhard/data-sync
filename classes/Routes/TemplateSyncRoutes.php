<?php


namespace DataSync\Routes;

use WP_REST_Server;

class TemplateSyncRoutes {

	const AUTH = __NAMESPACE__ . '\Controllers\Auth';
	public $controller_class = null;

	/**
	 * Instantiate RESTful Route
	 */
	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}

	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/templates/start_sync', array(
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this->controller_class, 'initiate' ),
				),
			) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/templates/sync', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this->controller_class, 'receive' ),
					'permission_callback' => array( $this::AUTH, 'authorize' ),
				),
			) );
	}
}
