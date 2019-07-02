<?php


namespace DataSync\Controllers;

use WP_REST_Server;
use DataSync\Helpers;
use stdClass;

/**
 * Class Log
 * @package DataSync
 */
class Log {


	public function __construct( $error = false ) {

		if ( false === $error ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {
			$log_entry = $this->create_log_entry( $error );

			if ( get_option( 'source_site' ) ) {
				$this->log( $log_entry );
			} else {
				$this->send_to_source( $log_entry );
			}
		}

	}

	public function create_log_entry( string $error ) {
		return current_time( 'F j, Y g:i a' ) . "\n" . 'FROM: ' . get_site_url() . "\n" . $error . "\n\n";
	}

	public function send_to_source( string $log_entry ) {
		// RECEIVER SIDE.
		$data            = new stdClass();
		$data->log_entry = $log_entry;

		$auth     = new Auth();
		$json     = $auth->prepare( $data, get_option( 'secret_key' ) );
		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
//		$body     = wp_remote_retrieve_body( $response );
//		print_r( $body );
	}

	/**
	 * Makes sure WP_Filesystem allows writing to error.log before firing log()
	 */
	public function check_filesystem() {
		$url   = wp_nonce_url( '/wp-admin/options-general.php?page=data-sync-options', 'error_log' );
		$creds = \request_filesystem_credentials( $url, '', false, false, null );
		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			\request_filesystem_credentials( $url, '', true, false, null );

			return false;
		}

		return true;
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/log',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_log' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);

	}

	public function save( WP_REST_Request $request ) {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->log( $data->log_entry );
	}

	/**
	 * @param $error
	 *
	 * Prepends errors to ../error.log
	 */
	public function log( $log_entry ) {

//		$this->check_filesystem();

		$file_text = $log_entry . self::get_log();

//		global $wp_filesystem;

//		$wp_filesystem->put_contents(
//			DATA_SYNC_PATH . 'error.log',
//			$file_text,
//			FS_CHMOD_FILE // predefined mode settings for WP files.
//		);

		file_put_contents(
			DATA_SYNC_PATH . 'error.log',
			$file_text,
//			FS_CHMOD_FILE // predefined mode settings for WP files.
		);
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public static function get_log() {
//		global $wp_filesystem;
//
//		return $wp_filesystem->get_contents(
//			DATA_SYNC_PATH . 'error.log'
//		);

		return file_get_contents( DATA_SYNC_PATH . 'error.log' );
	}

}
