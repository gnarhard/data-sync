<?php


namespace DataSync\Routes;

use WP_REST_Server;

class ConnectedSitesRoutes
{

	const AUTH = 'DataSync\Controllers\Auth';
	const MODEL = 'DataSync\Models\ConnectedSite';
	public $controller_class = null;

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}

    public function register_routes()
    {
        $registered = register_rest_route(
            DATA_SYNC_API_BASE_URL,
            '/connected_sites',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this::MODEL, 'get_all' ),
                    'permission_callback' => array( $this::AUTH, 'permissions' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this->controller_class, 'save' ),
                    'permission_callback' => array( $this::AUTH, 'permissions' ),
                ),
            )

        );

        $registered = register_rest_route(
            DATA_SYNC_API_BASE_URL,
            '/connected_sites/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this->controller_class, 'get' ),
                    'permission_callback' => array( $this::AUTH, 'permissions' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => 'ID of connected_site',
                            'type'              => 'int',
//                            'validate_callback' => 'is_numeric',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this->controller_class, 'delete' ),
                    'permission_callback' => array( $this::AUTH, 'permissions' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => 'ID of connected_site',
                            'type'              => 'int',
//                            'validate_callback' => 'is_numeric',
                        ),
                    ),
                ),
            )
        );
    }

}
