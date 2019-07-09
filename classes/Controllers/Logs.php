<?php


namespace DataSync\Controllers;

use DataSync\Models\Log;
use WP_REST_Server;
use DataSync\Helpers;
use stdClass;
use WP_REST_Response;

/**
 * Class Logs
 * @package DataSync
 */
class Logs {

	public $log_entry;
	public $url_source;
	public $error;
	public $log;

	public function __construct( $message = false, $error = false ) {

		if ( false === $message ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {

			$this->log             = new stdClass();
			$this->log->url_source = get_site_url();
			$this->error           = $message;
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

		$this->log->log_entry = '';

		if ( $error ) {
			$this->log->log_entry .= '<span style="color:red;">';
		} else {
			$this->log->log_entry .= '<span style="color:green;">';
		}

		$this->log->log_entry .= $this->error;

		$this->log->log_entry .= '</span>';
	}

	public function send_to_source() {
		// RECEIVER SIDE.
		$auth             = new Auth();
		$json             = $auth->prepare( $this->log, get_option( 'secret_key' ) );

		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log' );
		$response = wp_remote_post( $url, [ 'body' => $json ] ); // TODO: THIS IS THE REASON FOR CURL ERROR 28 (and anywhere else where we're sending something back to the source) because it is conflicting with the ongoing source curl? Even if I set the response from the server to be immediate, it still gives this error. . .


		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			$log = new Logs( 'Error in Logs()->send_to_source received from ' . get_option( 'data_sync_source_site_url' ) . '. ' . $response->get_error_message(), true );
			unset( $log );
		} else {
			print_r( wp_remote_retrieve_body( $response ) );
		}

	}

	public function save() {
		$data                  = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->log             = new stdClass();
		$this->log->log_entry  = $data->log_entry;
		$this->log->url_source = $data->url_source;
		$this->log();

		wp_send_json_success();
	}

	public function log() {
		Log::create( $this->log );
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public static function get_log() {
		return Log::get_all_and_sort( [ 'datetime' => 'DESC' ] );
	}

	public function refresh_log() {
		include_once ABSPATH . 'wp-content/plugins/data-sync/views/admin/options/log.php';
		$data       = new stdClass();
		$data->html = wp_json_encode( \DataSync\display_log() );

		return $data;
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/log',
			array(
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'save' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
					// TODO: GET AUTH TO WORK!
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/log/get',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'refresh_log' ),
				),
			)
		);

	}
}
