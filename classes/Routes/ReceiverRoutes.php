<?php


namespace DataSync\Routes;

use WP_REST_Server;

/**
 *
 */
class ReceiverRoutes {

	const AUTH = 'DataSync\Controllers\Auth';

	/**
	 * Receiver constructor.
	 */
	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
		if ( '0' === get_option( 'source_site' ) ) {
			add_action( 'rest_pre_dispatch', [ $this, 'authorize_source_cors_http_header' ] );
		}
	}


	public function authorize_source_cors_http_header() {
		if ( get_option( 'data_sync_source_site_url' ) ) {
			header( "Access-Control-Allow-Origin: " . get_option( 'data_sync_source_site_url' ) );
		}
	}

	/**
	 *
	 */
	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/sync', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this::CONTROLLER, 'sync' ),
				'permission_callback' => array( $this::AUTH, 'authorize' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/overwrite', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this::CONTROLLER, 'sync' ),
				'permission_callback' => array( $this::AUTH, 'authorize' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/start_fresh', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this::CONTROLLER, 'start_fresh' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/receiver/get_data', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this::CONTROLLER, 'give_receiver_data' ),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/receiver/prevalidate', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this::CONTROLLER, 'prevalidate' ),
			),
		) );
	}

}
