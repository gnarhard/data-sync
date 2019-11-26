<?php

namespace DataSync\Routes;

use WP_REST_Server;

/**
 *
 */
class OptionsRoutes {


	const AUTH = __NAMESPACE__ . '\Controllers\Auth';
	public $controller_class = null;

	public function __construct( $controller ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->controller_class = $controller;
	}


	public function register_routes() {
		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/options/(?P<option>[a-zA-Z-_]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this->controller_class, 'get' ),
				'permission_callback' => array( $this::AUTH, 'permissions' ),
				'args'                => array(
					'option' => array(
						'description' => 'Option key',
						'type'        => 'string',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this->controller_class, 'save' ),
				'permission_callback' => array( $this::AUTH, 'permissions' ),
				'args'                => array(
					'option' => array(
						'description' => 'Option key',
						'type'        => 'string',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this->controller_class, 'delete' ),
				'permission_callback' => array( $this::AUTH, 'permissions' ),
				'args'                => array(
					'option' => array(
						'description' => 'Option key',
						'type'        => 'string',
					),
				),
			),
		) );

		$registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/settings_tab/(?P<tab>[a-zA-Z-_]+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this->controller_class, 'get_settings_tab_html' ),
				'args'     => array(
					'tab' => array(
						'description' => 'Tab to get',
						'type'        => 'string',
					),
				),
			),
		) );

	}

}
