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

	public function get_site_secret_key( int $receiver_site_id ) {

		if ( $receiver_site_id ) {
			$request = new WP_REST_Request();
			$request->set_method( 'GET' );
			$request->set_route( '/' . DATA_SYNC_API_BASE_URL . '/connected_sites/' . $receiver_site_id );
			$request->set_url_params( array( 'id' => $receiver_site_id ) );
			$request->set_query_params( array( 'nonce' => wp_create_nonce( 'data_sync_api' ) ) );

			$response            = rest_do_request( $request );
			$connected_site_data = $response->get_data();

			return $connected_site_data->secret_key;
		}

	}

	public function prepare( $data, $secret_key ) {

		if ( ( ! property_exists($data, 'receiver_site_id' ) ) || ( null === $data->receiver_site_id ) ) {
			$data->receiver_site_id = get_option( 'data_sync_receiver_site_id' );
		}

		$json_decoded_data = json_decode( wp_json_encode( $data ) ); // DO THIS TO MAKE SIGNATURE CONSISTENT. JSON DOESN'T RETAIN OBJECT CLASS TITLES.
		$data->sig         = (string) $this->create_signature( $json_decoded_data, $secret_key );

		return wp_json_encode( $data );
	}

	public static function authorize() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$auth = new Auth();

		if ( get_option( 'secret_key' ) ) {
			return $auth->verify_signature( $data, get_option( 'secret_key' ) ); // Try getting option if receiver trying to authorize source.
		} else if ( ( property_exists($data, 'receiver_site_id' ) ) && ( null !== $data->receiver_site_id ) ) {
			// Get secret key of connected site if source is trying to authorize a request from a receiver.
			$secret_key_of_receiver = $auth->get_site_secret_key( $data->receiver_site_id );

			return $auth->verify_signature( $data, $secret_key_of_receiver );
		} else {
			$error_msg = 'ERROR: Failed to authorize cross-site connection.';
			$error_msg .= '<br>' . 'Secret Key: ' . get_option( 'secret_key' );
			$error_msg .= '<br>' . 'Receiver Site ID: ' . $data->receiver_site_id;
//			echo $error_msg;die();
			new Log( $error_msg, true );

			return false;
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

		$signature_sent     = $data->sig;
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