<?php


namespace DataSync;

use WP_REST_Server;


/**
 * Class Routes
 * @package DataSync
 */
class Routes {

	/**
	 * @var string
	 *
	 * Default prepended string for every endpoint
	 */
	public static $namespace = 'data-sync/v1';

	/**
	 * Routes constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register' ] );
	}

	/**
	 * Register All RESTful Routes associated with plugin functionality
	 */
	public function register() {

		register_rest_route(
			self::$namespace,
			'/settings/(?P<setting>[a-zA-Z-_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Options', 'get' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
					'args'                => array(
						'setting' => array(
							'description'       => 'Setting key',
							'type'              => 'string',
							'validate_callback' => function ( $param, $request, $key ) {
								return true;
							},
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( 'Options', 'save' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
					'args'                => array(
						'setting' => array(
							'description'       => 'Setting key',
							'type'              => 'string',
							'validate_callback' => function ( $param, $request, $key ) {
								return true;
							},
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( 'Options', 'delete' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
					'args'                => array(
						'setting' => array(
							'description'       => 'Setting key',
							'type'              => 'string',
							'validate_callback' => function ( $param, $request, $key ) {
								return true;
							},
						),
					),
				),
			)
		);

		register_rest_route(
			self::$namespace,
			'/connected_sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'ConnectedSites', 'get' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( 'ConnectedSites', 'save' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( 'ConnectedSites', 'delete' ),
					'permission_callback' => array( 'Auth', 'permissions' ),
				),
			)
		);
	}

}