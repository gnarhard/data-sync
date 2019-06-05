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
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Generate a signature string for the supplied data given a key.
	 *
	 * @param object $data
	 * @param string $key
	 *
	 * @return string
	 */
	public function create_signature( object $data, string $key ) {

		$data_array = (array) $data;
		if ( isset( $data_array['sig'] ) ) {
			unset( $data_array['sig'] );
		}

		$data_array = array_map( array( $this, 'sanitize_signature_data' ), $data_array );
		ksort( $data_array );
		$flat_data = implode( '', $data_array );

		return base64_encode( hash_hmac( 'sha1', $flat_data, $key, true ) );

	}


	public function verify_signature( $data, $key ) {

		$data_array = (array) $data;

		if ( empty( $data_array['sig'] ) ) {
			return false;
		}
		if ( isset( $data_array['nonce'] ) ) {
			unset( $data_array['nonce'] );
		}
		$temp               = $data_array;
		$computed_signature = $this->create_signature( $temp, $key );

		return $computed_signature === $data_array['sig'];
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