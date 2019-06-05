<?php


namespace DataSync\Controllers;

use Exception;
use WP_Error;
use WP_REST_Response;

class Auth {

	private $password = 'x&J8vQxxrI9@mnGUWaDpQtsO';

	/**
	 * @var array
	 *
	 * Will contain username and password for authorized user
	 */
	private $logins = array();

	public function __construct() {

		add_filter( 'rest_authentication_errors', [ $this, 'verify_user' ] );
	}

	public function verify_user( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
		}
		return $result;
	}

//	public static function verify_request( string $nonce ) {
//		$response = wp_verify_nonce( $nonce, 'data_push' );
//		if ( false === $response ) {
//			$error    = new WP_Error( 'nonce_error', 'Nonce Error: Nonce invalid.', array( 'status' => 403 ) );
//			$response = new WP_REST_Response( $error );
//			$response->set_status( 501 );
//
//			return $response;
//		} elseif ( 2 === $response ) {
//			$error    = new WP_Error( 'nonce_error', 'Nonce Error: Too long since nonce was created.', array( 'status' => 403 ) );
//			$response = new WP_REST_Response( $error );
//			$response->set_status( 501 );
//
//			return $response;
//		}
//	}

//	public function authenticate_site( $url ) {
//
//		$token_url = trailingslashit( $url ) . 'wp-json/jwt-auth/v1/token';
//
//		// Use key 'http' even if you send the request to https://.
//		$args = array(
//			'method'      => 'POST',
//			'timeout'     => 45,
//			'redirection' => 5,
//			'httpversion' => '1.0',
//			'blocking'    => true,
//			'headers'     => array(),
//			'body'        => $this->logins,
//		);
//
////		$args = stream_context_create( $options );
//
//		$user_can_login_on_source = $this->try_login();
//
//		if ( $user_can_login_on_source ) {
//			try {
//				$response = wp_remote_post( $token_url, $args );
//
//				if ( is_wp_error( $response ) ) {
//					$error = new Error();
//					( $error ) ? $error->log( $response->get_error_message() . "\n" ) : null;
//				} else {
//					return wp_remote_retrieve_body( $response );
//				}
//
//			} catch ( Exception $e ) {
//				$error = new Error();
//				( $error ) ? $error->log( 'Caught exception: ' . $e->getMessage() . "\n" ) : null;
//			}
//		}
//
//	}
//
//	public function validate( string $site_url, $auth_response ) {
//
//		$token         = json_decode( $auth_response )->token;
//		$url           = trailingslashit( $site_url ) . 'wp-json/jwt-auth/v1/token/validate';
//		$args          = array(
//			'headers' => array(
//				'Authorization' => 'Bearer ' . $token,
//			),
//		);
//		$json_response = wp_remote_post( $url, $args );
//		$json          = json_decode( $json_response['body'] );
//
//		return ( 200 === $json->data->status );
//	}

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}










	/**
	 * Generate a signature string for the supplied data given a key.
	 *
	 * @param array  $data
	 * @param string $key
	 *
	 * @return string
	 */
	public function create_signature( $data, $key ) {
		if ( isset( $data['sig'] ) ) {
			unset( $data['sig'] );
		}
		$data = array_map( array( $this, 'sanitize_signature_data' ), $data );
		ksort( $data );
		$flat_data = implode( '', $data );
		return base64_encode( hash_hmac( 'sha1', $flat_data, $key, true ) );
	}


	public function verify_signature( $data, $key ) {
		if ( empty( $data['sig'] ) ) {
			return false;
		}
		if ( isset( $data['nonce'] ) ) {
			unset( $data['nonce'] );
		}
		$temp               = $data;
		$computed_signature = $this->create_signature( $temp, $key );
		return $computed_signature === $data['sig'];
	}

	// Generates our secret key
	public function generate_key( $length = 40 ) {
		$keyset = 'abcdefghijklmnopqrstuvqxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/';
		$key    = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$key .= substr( $keyset, wp_rand( 0, strlen( $keyset ) - 1 ), 1 );
		}

		return $key;
	}

}