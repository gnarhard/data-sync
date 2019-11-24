<?php


namespace DataSync\Routes;

use DataSync\Controllers\SourceData;
use WP_REST_Server;

/**
 *
 */
class SourceDataRoutes {

	const AUTH = 'DataSync\Controllers\Auth';

	/**
	 * Instantiate RESTful Route
	 */
	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}


	/**
	 * Register RESTful routes for Data Sync API
	 *
	 */
	public function register_routes() {

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source_data/(?P<action>[a-zA-Z-_]+)/(?P<source_post_id>\d+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_source_data' ),
				'args'     => array(
					'action'         => array(
						'description' => 'Action to tell backend which content to provide.',
						'type'        => 'string',
					),
					'source_post_id' => array(
						'description' => 'Source Post ID',
						'type'        => 'int',
					),
				),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source/start_fresh', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'start_fresh' ),
			),
		) );


		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source_data/prep/(?P<source_post_id>\d+)/(?P<receiver_site_id>\d+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'prep' ),
				'args'     => array(
					'source_post_id'   => array(
						'description' => 'Source Post ID',
						'type'        => 'int',
					),
					'receiver_site_id' => array(
						'description' => 'Receiver Site ID',
						'type'        => 'int',
					),
				),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/prevalidate', array(
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this->controller_class, 'prevalidate' ),
			),
		) );

	}

}