<?php


namespace DataSync\Controllers;


use Exception;

class Auth {

	/**
	 * @var string
	 *
	 * Default prepended string for every endpoint
	 */
	public $namespace = 'data-sync-api/v1';

	/**
	 * @var string
	 *
	 * WordPress username for CORs authentication
	 * MUST BE ON EVERY SITE
	 */
	private $username = 'data_sync';

	/**
	 * @var string
	 * WordPress password for CORs authentication
	 * MUST BE ON EVERY SITE
	 */
	private $password = 'x&J8vQxxrI9@mnGUWaDpQtsO';


	/**
	 * @var array
	 *
	 * Will contain username and password for authorized user
	 */
	private $logins = array();

	public function __construct() {
		$this->logins = array(
			'username' => $this->username,
			'password' => $this->password,
		);
	}

	private function get_token( $data_receiver_url ) {

		$token_url = trailingslashit( $data_receiver_url ) . 'wp-json/jwt-auth/v1/token';

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
					return $response['body'];
				}

			} catch ( Exception $e ) {
				$error = new Error();
				( $error ) ? $error->log( 'Caught exception: ' . $e->getMessage() . "\n" ) : null;
			}
		}

	}

	private function try_login() {

		$user = wp_authenticate( $this->username, $this->password );

		if ( $user->get_error_message() ) {
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
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

}