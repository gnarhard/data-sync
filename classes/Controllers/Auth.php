<?php


namespace DataSync\Controllers;

use Exception;
use WP_Error;
use WP_REST_Response;
USE WP_REST_Request;

class Auth {

	/**
	 * @var array
	 *
	 * Will contain username and password for authorized user
	 */
	private $logins = array();

	public function __construct() {
//		add_filter( 'rest_authentication_errors', [ $this, 'verify_user' ] );
		add_action( 'init', 'add_cors_http_header' );
		add_action( 'rest_api_init', [ $this, 'allow_cors_headers_on_endpoints' ], 15 );

	}

	public function allow_cors_headers_on_endpoints() {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', function ( $value ) {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );

			return $value;

		} );
	}

	public function add_cors_http_header() {
		header( "Access-Control-Allow-Origin: *" );
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

	public function verify_request( string $nonce ) {
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

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public static function permissions( WP_REST_Request $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		} else {
			if ( $request->get_param( 'nonce' ) ) {
				return wp_verify_nonce( $request->get_param( 'nonce' ), 'data_sync_api' );
			}
		}
	}

	/**
	 * Generate a signature string for the supplied data given a key.
	 *
	 * @param object $data
	 * @param string $key
	 *
	 * @return string
	 */
	public function create_signature( $data, string $key ) {

		if ( isset( $data->sig ) ) {
			unset( $data->sig );
		}

		return base64_encode( hash_hmac( 'sha1', serialize( $data ), $key, true ) );

	}


	public function verify_signature( $data, string $key ) {

		if ( empty( $data->sig ) ) {
			return false;
		}

		$signature_sent = $data->sig;
		$signature_received = $this->create_signature( $data, $key );

		return $signature_received === $signature_sent;
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

	public function sanitize_signature_data( $value ) {
		if ( is_bool( $value ) ) {
			$value = $value ? 'true' : 'false';
		}

		return $value;
	}

}