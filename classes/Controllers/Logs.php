<?php


namespace DataSync\Controllers;

use DataSync\Models\ConnectedSite;
use DataSync\Controllers\ConnectedSites;
use DataSync\Models\Log;
use DataSync\Routes\LogsRoutes;
use DataSync\Tools\Helpers;
use stdClass;
use WP_REST_Request;
use function DataSync\display_log;

/**
 * Class Logs
 *
 * @package DataSync
 */
class Logs {
	public $log_entry;
	public $url_source;
	public $error;
	public $log;

	public function __construct() {
		new LogsRoutes( $this );
	}

	public function set( $message = false, $error = false, $type = false ) {
		$this->log             = new stdClass();
		$this->log->url_source = get_site_url();
		$this->log->datetime   = current_time( 'mysql', 1 );
		$this->error           = $message;

		if ( $type ) {
			$this->log->type = $type;
		} else {
			$this->log->type = '';
		}

		$this->create_log_entry( $error );
		$this->log();
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

	public function log() {
		$result = Log::create( $this->log );

		if ( is_wp_error( $result ) ) {
			$logs = new Logs();
			$logs->set( $result->get_error_message(), true );
		}

	}

	public static function retrieve_receiver_logs( $data_sync_start_time ) {
		$connected_sites = (array) ConnectedSite::get_all();

		$all_data = array();

		foreach ( $connected_sites as $site ) {

			$site     = ConnectedSites::get_api_url( $site );
			$url      = $site->api_url . DATA_SYNC_API_BASE_URL . 'log/fetch_receiver';
			$args     = array(
				'body' => array( 'datetime' => $data_sync_start_time ),
			);
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				$logs = new Logs();
				$logs->set( 'Error in Logs::retrieve_receiver_logs received from ' . get_site_url() . '. ' . $response->get_error_message(), true );

				return $response;
			}

			$parsed_response = json_decode( wp_remote_retrieve_body( $response ) );
			if ( ! empty( $parsed_response ) ) {
				$all_data[] = $parsed_response;
			}
		}

		return (array) $all_data;
	}

	public static function save_to_source( array $receiver_logs ) {
		foreach ( $receiver_logs as $site_logs ) {
			if ( ! empty( $site_logs ) ) {
				foreach ( $site_logs as $log ) {
					$result = Log::create( $log );
				}
			} else {
				$logs = new Logs();
				$logs->set( 'No logs pulled from site.', true );
			}
		}
	}


	public function create( WP_REST_Request $request ) {
		$receiver_logs = json_decode( $request->get_body() );
		self::save_to_source( $receiver_logs );
		wp_send_json_success();
	}

	/**
	 * @return mixed
	 */
	public static function get_log( WP_REST_Request $request = null ) {
		if ( $request ) {
			$datetime = $request->get_param( 'datetime' );
			if ( $datetime ) {
				return Log::get_all_and_sort( array( 'datetime' => 'DESC' ), $datetime );
			}
		} else {
			return Log::get_all_and_sort( array( 'datetime' => 'DESC' ) );
		}
	}

	public function refresh_log() {
		include_once DATA_SYNC_PATH . 'public/views/admin/options/log.php';
		$data       = new stdClass();
		$data->html = wp_json_encode( display_log() );

		return $data;
	}

	public function delete_all() {
		$result = Log::delete_all();

		wp_send_json( $result );
	}

}
