<?php


namespace DataSync\Routes;

use WP_REST_Server;

/**
 * Class Logs
 * @package DataSync
 */
class LogsRoutes {

	const AUTH = 'DataSync\Controllers\Auth';
	public $controller_class = null;

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}


	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, 'log/get', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'refresh_log' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, 'log/create', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this->controller_class, 'create' ),
				'permission_callback' => array( $this::AUTH, 'permissions' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, 'log/delete', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this->controller_class, 'delete_all' ),
				'permission_callback' => array( $this::AUTH, 'permissions' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, 'log/fetch_receiver', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_log' ),
			),
		) );
	}
}
