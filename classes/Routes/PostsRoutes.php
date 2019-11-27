<?php


namespace DataSync\Routes;

use WP_REST_Server;


class PostsRoutes {


	const AUTH = 'DataSync\Controllers\Auth';
	public $controller_class = null;

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}

	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/post_meta/(?P<id>\d+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_custom_post_meta' ),
				'args'     => array(
					'id' => array(
						'description'       => 'ID of post',
						'type'              => 'int',
						'validate_callback' => 'is_numeric',
					),
				),
			),
		) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/posts/(?P<id>\d+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_post' ),
				'args'     => array(
					'id' => array(
						'description' => 'ID of post',
						'type'        => 'int',
					),
				),
			),
		) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/posts/all', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_all_posts' ),
			),
		) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/status_data/(?P<post_id>\d+)', array(
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this->controller_class, 'get_syndicated_post_status_data' ),
				'args'     => array(
					'post_id' => array(
						'description' => 'ID of post',
						'type'        => 'int',
					),
				),
			),
		) );

	}
}
