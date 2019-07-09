<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;
use WP_REST_Server;

class TemplateSync {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function push() {


	}

	public function sync() {

		$template_dir    = DATA_SYNC_PATH . '/templates';
		$files           = scandir( $template_dir );
		$source_urls     = array();
		$connected_sites = (array) ConnectedSites::get_all()->get_data();
		$plugin_dir      = '/plugins/data-sync/';

		foreach ( $files as $file ) {
			if ( '.' === $file ) {
				continue;
			} elseif ( '..' === $file ) {
				continue;
			}

			foreach ( $connected_sites as $connected_site ) {
				$source_url   = DATA_SYNC_URL . 'templates/' . $file;
				$receiver_url = $connected_site->url . $plugin_dir . 'templates/' . $file;
				$result       = File::copy( $source_url, $receiver_url );
			}

			wp_send_json_success();

		}


	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/templates/sync',
			array(
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'sync' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
					// TODO: MAKE SURE AUTHORIZE WORKS
				),
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'push' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

}