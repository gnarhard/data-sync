<?php


namespace DataSync;

use WP_REST_Server;


class Routes {

	public function register() {

		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'API', 'get_settings' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( 'API', 'update_settings' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( 'API', 'delete_setting' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
			)
		);

//		register_rest_route(
//			$this->namespace,
//			'/settings/(?P<setting>[a-zA-Z-_]+)',
//			array(
//				'methods'              => 'GET',
//				'callback'             => array( $this, 'get_setting' ),
//				'args'                 => array(
//					'setting' => array(
//						'description'       => 'Setting key',
//						'type'              => 'string',
//						'validate_callback' => function ( $param, $request, $key ) {
//							return true;
//						},
//					),
//				),
//				'permissions_callback' => array( $this, 'permissions' ),
//			)
//		);

		register_rest_route(
			$this->namespace,
			'/connected_sites',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'ConnectedSites', 'get' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( 'ConnectedSites', 'save' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( 'ConnectedSites', 'delete' ),
				'permission_callback' => array( 'Auth', 'permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/sync',
			array(
				'methods'              => 'POST',
				'callback'             => array( 'API', 'sync' ),
				'args'                 => array(),
				'permissions_callback' => array( 'Auth', 'permissions' ),
			)
		);
	}

}