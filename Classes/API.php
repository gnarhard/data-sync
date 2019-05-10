<?php namespace DataSync;


class API {

	/**
	 * Add routes
	 */
	public function addRoutes( ) {
		register_rest_route( 'data-sync-api/v1', '/settings',
			array(
				'methods'         => 'POST',
				'callback'        => array( $this, 'update_settings' ),
				'args' => array(
					'industry' => array(
						'type' => 'string',
						'required' => false,
						'sanitize_callback' => 'sanitize_text_field'
					),
					'amount' => array(
						'type' => 'integer',
						'required' => false,
						'sanitize_callback' => 'absint'
					)
				),
				'permissions_callback' => array( $this, 'permissions' )
			)
		);
		register_rest_route( 'data-sync-api/v1', '/settings',
			array(
				'methods'         => 'GET',
				'callback'        => array( $this, 'get_settings' ),
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
		$settings = array(
//			'industry' => $request->get_param( 'industry' ),
//			'amount' => $request->get_param( 'amount' )
		);
		Settings::save($settings);
		return rest_ensure_response( Settings::get())->set_status( 201 );
	}
	/**
	 * Get settings via API
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_settings( WP_REST_Request $request ){
		return rest_ensure_response( Settings::get());
	}

}