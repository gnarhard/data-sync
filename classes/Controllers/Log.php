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

	public $log_entry;
	public $error;

	public function __construct( $message = false, $error = false ) {

		if ( false === $message ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {
			$this->error = $message;
			$this->create_log_entry( $error );

			if ( '1' === get_option( 'source_site' ) ) {
				$this->log();
			} elseif ( '0' === get_option( 'source_site' ) ) {
				$this->send_to_source();
			} else {
				$this->error .= 'ERROR: Source or receiver option not set to allow proper logging.';
				$this->log();
			}
		}

	}

	public function create_log_entry( $error ) {

		$this->log_entry = current_time( 'g:i a F j, Y' ) . ' from ' . get_site_url() . '<br>';

		if ( $error ) {
			$this->log_entry .= '<span style="color:red;">';
		} else {
			$this->log_entry .= '<span style="color:green;">';
		}

		$this->log_entry .= $this->error;

		$this->log_entry .= '</span>' . '<br><br>' . "\n";
//		echo $this->log_entry;
	}

	public function send_to_source() {
		// RECEIVER SIDE.
		$data            = new stdClass();
		$data->log_entry = $this->log_entry;
		$auth     = new Auth();
		$json     = $auth->prepare( $data, get_option( 'secret_key' ) );

//		print_r($json);die();
		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
		echo 'send to source';
		$body                          = wp_remote_retrieve_body( $response );
//		var_dump ((object) json_decode( file_get_contents( 'php://input' ) ) );die();
	}

	public function save() {
		$data            = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->log_entry = $data->log_entry;

		$this->log();
	}

	/**
	 *
	 * Prepends errors to ../error.log
	 */
	public function log() {

		$file_text = $this->log_entry . self::get_log();

		file_put_contents(
			DATA_SYNC_PATH . 'error.log',
			$file_text,
			);
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public static function get_log() {
		$file             = file_get_contents( DATA_SYNC_PATH . 'error.log' );
//		$exploded_file    = explode( "\n", $file );
//		$length_to_return = 5000;
//		$file_to_return   = '';
//
//		for ( $i = 0; $i < $length_to_return; $i ++ ) {
//			$file_to_return .= $file[ $i ];
//		}

		return $file;
//		return $file_to_return;
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
					// TODO: GET AUTH TO WORK!
				),
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_log' ),
				),
			)
		);

	}
}
