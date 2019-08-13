<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;
use WP_REST_Server;
use stdClass;
use DataSync\Controllers\Logs;

class TemplateSync {


	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/templates/start_sync',
			array(
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'initiate' ),
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/templates/sync',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'receive' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

	public function initiate() {

		$template_dir = DATA_SYNC_PATH . '/templates';
		$files        = scandir( $template_dir );

		$connected_sites = (array) ConnectedSites::get_all()->get_data();

		foreach ( $files as $file ) {
			if ( '.' === $file ) {
				continue;
			} elseif ( '..' === $file ) {
				continue;
			}

			foreach ( $connected_sites as $connected_site ) {

				$source_data                  = new stdClass();
				$source_data->filename        = $file;
				$source_data->source_base_url = get_site_url();
				$source_data->remote_file_url = DATA_SYNC_URL . 'templates/' . $file;
				$source_data->file_contents   = file_get_contents( $template_dir . '/' . $file );

				$this->push( $connected_site, $source_data );
			}
		}

		wp_send_json_success();

	}

	public function push( $connected_site, $source_data ) {

		$source_data->receiver_site_id = (int) $connected_site->id;
		$source_data->start_time       = (string) current_time( 'mysql' );
		$auth                          = new Auth();
		$json                          = $auth->prepare( $source_data, $connected_site->secret_key );
		$url                           = trailingslashit( $connected_site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/templates/sync';
		$response                      = wp_remote_post( $url, [ 'body' => $json ] );

		$receiver_logs = Logs::retrieve_receiver_logs( $source_data->start_time );
		Logs::save_to_source( $receiver_logs );

		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			$log = new Logs( 'Error in TemplateSync->push() received from ' . $connected_site->url . '. ' . $response->get_error_message(), true );
			unset( $log );
		} else {
			if ( get_option( 'show_body_responses' ) ) {
				if ( get_option( 'show_body_responses' ) ) {
					print_r( wp_remote_retrieve_body( $response ) );
				}
			}
		}

	}

	public function receive() {

		$source_data = (object) json_decode( file_get_contents( 'php://input' ) );

		$this->sync( $source_data );

		$log = new Logs( 'Template sync complete.' );
		unset( $log );

	}

	public function sync( $source_data ) {

		$result = File::copy( $source_data->source_base_url, $source_data->remote_file_url, $source_data->file_contents );
//TODO: FIX RETURN RESULT
		if ( $result ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json( $result );
		}

	}


}
