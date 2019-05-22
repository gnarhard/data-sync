<?php


namespace DataSync\Controllers;

use WP_REST_Request;
use DataSync\Models\ConnectedSite;
use WP_REST_Server;


class ConnectedSites {

	private $table_name = 'data_sync_connected_sites';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/connected_sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				),
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'save' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				),
				array(
					'methods'  => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				),
			)
		);
	}

	public function get( WP_REST_Request $request ) {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			ConnectedSite::create_db_table();
		}

		$id = $request;
		$wpdb->query( $wpdb->prepare( 'SELECT * FROM %s WHERE id = %d', $this->table_name, $id ) );
	}

	public function save( WP_REST_Request $request ) {

//		connectedSite::save();
	}

	public function delete( WP_REST_Request $request ) {
		ConnectedSite::delete();
	}

	private function table_exists() {
		global $wpdb;

		return in_array( $this->table_name, $wpdb->tables );
	}

}