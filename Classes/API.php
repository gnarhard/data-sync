<?php namespace DataSync;
use WP_REST_Request;
use WP_REST_Response;

class API {

	public $namespace = 'data-sync-api/v1';

  public function __construct()
  {
    add_action('rest_api_init', [$this, 'add_routes'] );
  }

  /**
	 * Add routes
	 */
	public function add_routes( ) {
		register_rest_route( $this->namespace, '/settings',
			array(
				'methods'         => 'POST',
				'callback'        => array( $this, 'update_settings' ),
				'args' => array(
//					'industry' => array(
//						'type' => 'string',
//						'required' => false,
//						'sanitize_callback' => 'sanitize_text_field'
//					),
//					'amount' => array(
//						'type' => 'integer',
//						'required' => false,
//						'sanitize_callback' => 'absint'
//					)
				),
				'permissions_callback' => array( $this, 'permissions' )
			)
		);
		register_rest_route( $this->namespace, '/settings',
			array(
				'methods'         => 'GET',
				'callback'        => array( $this, 'get_settings' ),
				'args'            => array(
				),
				'permissions_callback' => array( $this, 'permissions' )
			)
		);
		register_rest_route( $this->namespace, '/sync',
			array(
				'methods'         => 'POST',
				'callback'        => array( $this, 'sync' ),
				'args'            => array(
				),
				'permissions_callback' => array( $this, 'permissions' )
			)
		);
	}
	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions(){
		return current_user_can( 'manage_options' );
	}
	/**
	 * Update settings
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_settings( WP_REST_Request $request ){
		Settings::save($request->get_params());
		return rest_ensure_response( Settings::get($request->get_params()))->set_status( 201 );
	}
	/**
	 * Get settings via API
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_settings( WP_REST_Request $request ){
		return rest_ensure_response( Settings::get());
	}

	public function sync() {

	}

}