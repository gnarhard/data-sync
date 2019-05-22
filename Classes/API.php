<?php namespace DataSync;

use WP_REST_Request;
use Exception;
use DataSync\Error as Error;
use Routes;

//use WP_REST_Response;

/**
 * Class API
 * @package DataSync
 */
class API {

	/**
	 * API constructor.
	 *
	 * Adds RESTful routes to WordPress
	 */
	public function __construct() {

		add_action( 'rest_api_init', [ 'Routes', 'register' ] );
	}


	/**
	 * Update settings
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_settings( WP_REST_Request $request ) {
		$success = Settings::save_options( $request->get_params() );

		if ( $success ) {
			return rest_ensure_response( Settings::get_options( $request->get_params() ) )->set_status( 201 );
		} else {
			$error = new Error();
			( $error ) ? $error->log( 'Settings NOT saved.' . "\n" ) : null;
		}
	}

	public function get_setting( WP_REST_Request $request ) {

		$parameters  = $request->get_params();
		$setting_key = array_keys( $parameters );
		$setting     = $parameters[ $setting_key[0] ];

		return rest_ensure_response( Settings::get( $setting ) );
	}

	public function get_connected_sites( WP_REST_Request $request ) {
		return Settings::get_connected_sites();
	}

	public function save_connected_sites( WP_REST_Request $request ) {
		return Settings::save_connected_sites();
	}

	/**
	 * Get settings via API
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_settings( WP_REST_Request $request ) {
		return rest_ensure_response( Settings::get( $request->get_params() ) );
	}

	/**
	 *
	 */
	public function sync() {
		$this->get_token( 'https://datareceiver1.copperleaf.dev' );
	}

}