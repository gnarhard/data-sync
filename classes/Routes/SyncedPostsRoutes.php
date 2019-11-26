<?php


namespace DataSync\Routes;

use WP_REST_Server;

class SyncedPostsRoutes {

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
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/sync_post/', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this->controller_class, 'save_to_source' ),
					'permission_callback' => array( $this::AUTH, 'permissions' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this->controller_class, 'get' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/all', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this->controller_class, 'get_all' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/retrieve_from_receiver', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this->controller_class, 'get_after_date' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/delete_receiver_post/', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this->controller_class, 'delete_post' ),
					'permission_callback' => array( $this::AUTH, 'authorize' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/delete_synced_post/', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this->controller_class, 'delete_synced_post' ),
					'permission_callback' => array( $this::AUTH, 'authorize' ),
				),
			) );
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/synced_posts/(?P<receiver_site_id>\d+)/(?P<source_post_id>\d+)', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this->controller_class, 'get' ),
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
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/syndicated_post/(?P<post_id>\d+)', array(
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this->controller_class, 'display_post' ),
					'args'     => array(
						'post_id' => array(
							'description' => 'Post ID',
							'type'        => 'int',
						),
					),
				),
			) );
	}
}
