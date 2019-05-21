<?php namespace DataSync;

use WP_REST_Request;
use Exception;
use DataSync\Error as Error;
//use WP_REST_Response;

/**
 * Class API
 * @package DataSync
 */
class API {

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

	/**
	 * API constructor.
	 *
	 * Adds RESTful routes to WordPress
	 */
	public function __construct() {

		add_action( 'rest_api_init', [ $this, 'add_routes' ] );

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
	 * Add routes
	 */
	public function add_routes() {

		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'update_settings' ),
				'args'                 => array(
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
				'permissions_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				'methods'              => 'GET',
				'callback'             => array( $this, 'get_settings' ),
				'args'                 => array(),
				'permissions_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/sync',
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'sync' ),
				'args'                 => array(),
				'permissions_callback' => array( $this, 'permissions' ),
			)
		);
	}

	/**
	 * Check request permissions
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Update settings
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_settings( WP_REST_Request $request ) {
		Settings::save( $request->get_params() );

		return rest_ensure_response( Settings::get( $request->get_params() ) )->set_status( 201 );
	}

	/**
	 * Get settings via API
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_settings( WP_REST_Request $request ) {
		return rest_ensure_response( Settings::get() );
	}

	/**
	 *
	 */
	public function sync() {
		$this->get_token( 'https://datareceiver1.copperleaf.dev' );
	}

}