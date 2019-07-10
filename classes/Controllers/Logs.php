<?php


namespace DataSync\Controllers;

use DataSync\Models\Log;
use WP_REST_Server;
use DataSync\Helpers;
use stdClass;
use WP_REST_Response;
use WP_REST_Request;

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
			$this->log->datetime   = current_time( 'Y-m-d H:i:s.u' );
			$this->error           = $message;

			$this->create_log_entry( $error );
			$this->log();
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
		$auth = new Auth();
		$json = $auth->prepare( $this->log, get_option( 'secret_key' ) );

		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log' );
		$response = wp_remote_post( $url, [ 'body' => $json ] ); // THIS IS THE REASON FOR CURL ERROR 28 (and anywhere else where we're sending something back to the source) because it is conflicting with the ongoing source curl? Even if I set the response from the server to be immediate, it still gives this error. . .


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
		$this->log->datetime   = current_time( 'Y-m-d H:i:s.u' );
		$this->log();

		wp_send_json_success();
	}

	public function log() {
		Log::create( $this->log );
	}

	public static function retrieve_receiver_logs( $data_sync_start_time ) {

		$connected_sites = (array) ConnectedSites::get_all()->get_data();
		$all_data        = array();

		foreach ( $connected_sites as $site ) {
			$url      = Helpers::format_url( trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log/fetch_receiver' );
			$args     = array(
				'body' => [ 'datetime' => $data_sync_start_time ],
			);
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in Logs::retrieve_receiver_logs received from ' . get_site_url() . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
//				print_r( wp_remote_retrieve_body( $response ) );
			}

			$all_data[] = json_decode( wp_remote_retrieve_body( $response ) );
		}

		return $all_data;

	}

	public static function save_to_source( array $receiver_logs ) {
		foreach ( $receiver_logs as $site_logs ) {
			foreach ( $site_logs as $log ) {
				$result = Log::create( $log );
			}
		}
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public static function get_log( WP_REST_Request $request = null ) {

		if ( $request ) {
			$datetime = $request->get_param( 'datetime' );
			if ( $datetime ) {
				return Log::get_all_and_sort( [ 'datetime' => 'DESC' ], $datetime );
			}
		} else {
			return Log::get_all_and_sort( [ 'datetime' => 'DESC' ] );
		}

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
					// TODO: AUTH DOESN'T WORK
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

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/log/fetch_receiver',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_log' ),
				),
			)
		);

	}
}
