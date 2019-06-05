<?php


namespace DataSync\Controllers;

use Exception;
use WP_Error;
use WP_REST_Response;

class Auth {

	/**
	 * @var array
	 *
	 * Will contain username and password for authorized user
	 */
	private $logins = array();

	public function __construct() {

		$user_data = get_user_by( 'slug', 'data_sync' );

		$this->logins = array(
			'username' => $user_data->user_login,
			'password' => $user_data->user_pass,
		);
	}

	public static function verify_request( string $nonce ) {
		$response = wp_verify_nonce( $nonce, 'data_push' );
		if ( false === $response ) {
			$error    = new WP_Error( 'nonce_error', 'Nonce Error: Nonce invalid.', array( 'status' => 403 ) );
			$response = new WP_REST_Response( $error );
			$response->set_status( 501 );

			return $response;
		} elseif ( 2 === $response ) {
			$error    = new WP_Error( 'nonce_error', 'Nonce Error: Too long since nonce was created.', array( 'status' => 403 ) );
			$response = new WP_REST_Response( $error );
			$response->set_status( 501 );

			return $response;
		}
	}

	public function authenticate_site( $url ) {

		$token_url = trailingslashit( $url ) . 'wp-json/jwt-auth/v1/token';

		// Use key 'http' even if you send the request to https://.
		$args = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $this->logins,
		);

//		$args = stream_context_create( $options );

		$user_can_login_on_source = $this->try_login();

		if ( $user_can_login_on_source ) {
			try {
				$response = wp_remote_post( $token_url, $args );

				if ( is_wp_error( $response ) ) {
					$error = new Error();
					( $error ) ? $error->log( $response->get_error_message() . "\n" ) : null;
				} else {
					return wp_remote_retrieve_body( $response );
				}

			} catch ( Exception $e ) {
				$error = new Error();
				( $error ) ? $error->log( 'Caught exception: ' . $e->getMessage() . "\n" ) : null;
			}
		}

	}

	public function validate( string $site_url, $auth_response ) {

		$token         = json_decode( $auth_response )->token;
		$url           = trailingslashit( $site_url ) . 'wp-json/jwt-auth/v1/token/validate';
		$args          = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		);
		$json_response = wp_remote_post( $url, $args );
		$json          = json_decode( $json_response['body'] );

		return ( 200 === $json->data->status );
	}

	private function try_login() {

		$user = wp_authenticate( $this->username, $this->password );

		if ( $user->get_error_message() ) {
			// TODO: FIX THIS.
			$error = new Error();
			( $error ) ? $error->log( "User doesn't exist on source site OR " . $user->get_error_message() . "\n" ) : null;
		} else {
			return true;
		}

	}

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}

}