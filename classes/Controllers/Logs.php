<?php


namespace DataSync\Controllers;

use DataSync\Models\Log;
use WP_REST_Server;
use DataSync\Helpers;
use stdClass;
use DataSync\display_log as display_log;

/**
 * Class Logs
 * @package DataSync
 */
class Logs {

	public $log_entry;
	public $url_source;
	public $error;

	public function __construct( $message = false, $error = false ) {
// TODO: LOG TO DB INSTEAD AND PULL RESULTS SORTED BY TIMESTAMP
		if ( false === $message ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {
			$this->url_source = get_site_url();
			$this->error      = $message;
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

		$this->log_entry = '';
		$this->log_entry .= current_time( 'g:i a F j, Y' ) . ' from ' . get_site_url();
		$this->log_entry .= '<br>';

		if ( $error ) {
			$this->log_entry .= '<span style="color:red;">';
		} else {
			$this->log_entry .= '<span style="color:green;">';
		}

		$this->log_entry .= $this->error;

		$this->log_entry .= '</span>';

//		echo $this->log_entry;
	}

	public function send_to_source() {
		// RECEIVER SIDE.
		$data             = new stdClass();
		$data->log_entry  = $this->log_entry;
		$data->url_source = $this->url_source;
		$auth             = new Auth();
		$json             = $auth->prepare( $data, get_option( 'secret_key' ) );

//		print_r($json);die();
		$url      = Helpers::format_url( trailingslashit( get_option( 'data_sync_source_site_url' ) ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/log' );
		$response = wp_remote_post( $url, [ 'body' => $json ] );
//		echo 'send to source';
		$body = wp_remote_retrieve_body( $response );
//		var_dump ((object) json_decode( file_get_contents( 'php://input' ) ) );die();
	}

	public function save() {
		$data             = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->log_entry  = $data->log_entry;
		$this->url_source = $data->url_source;

		print_r($data);
		$this->log();
	}

	public function log() {

		$data             = new stdClass();
		$data->log_entry  = $this->log_entry;
		$data->url_source = $this->url_source;
//		print_r($data);
//		Log::create( $data );

//
//		$file_text = $this->log_entry . self::get_log();
//
//		file_put_contents(
//			DATA_SYNC_PATH . 'error.log',
//			$file_text,
//			);
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public static function get_log() {

		return Log::get_all_and_sort( [ 'datetime' => 'ASC' ] );



//		$file          = file_get_contents( DATA_SYNC_PATH . 'error.log' );
//		$exploded_file = explode( "<br><br>", $file );
//		print_r( $exploded_file );
//		$length_to_return = 50;
//		$file_to_return   = '';
//
//		for ( $i = 0; $i < $length_to_return; $i ++ ) {
//			$file_to_return .= $exploded_file[ $i ];
//		}
//
////		return $file;
//		return $file_to_return;
	}

	public function refresh_log() {
		include_once ABSPATH . 'wp-content/plugins/data-sync/views/admin/options/log.php';
		\DataSync\display_log();
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
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'refresh_log' ),
				),
			)
		);

	}
}
