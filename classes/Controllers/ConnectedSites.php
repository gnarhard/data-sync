<?php


namespace DataSync\Controllers;

use WP_REST_Request;
use DataSync\Models\ConnectedSite;
use DataSync\Controllers\Error;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;


class ConnectedSites {

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
					'callback'            => array( $this, 'get_all' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				),
			)

		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/connected_sites/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'id' => array(
							'description'       => 'ID of connected_site',
							'type'              => 'int',
							'validate_callback' => 'is_numeric',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'id' => array(
							'description'       => 'ID of connected_site',
							'type'              => 'int',
							'validate_callback' => 'is_numeric',
						),
					),
				),
			)
		);
	}

	public function get( WP_REST_Request $request ) {
		$connected_site_id   = $request->get_param( 'id' );
		$connected_site_data = ConnectedSite::get( $connected_site_id );

		return $connected_site_data[0];
	}

	public static function get_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . ConnectedSite::$table_name;
		$result     = $wpdb->get_results( 'SELECT * FROM ' . $table_name );
		$response   = new WP_REST_Response( $result );
		$response->set_status( 201 );

		return $response;
	}

	public function save( WP_REST_Request $request ) {
//		if ( ! $this->table_exists() ) {
//			ConnectedSite::create_db_table();
//		}

		$new_data = array();

		foreach ( $request->get_params() as $data ) {
			if ( in_array( 'id', array_keys( $data ) ) ) {
				ConnectedSite::update( $data );
			} else {
				$new_id = ConnectedSite::create( $data );
				if ( is_numeric( $new_id ) ) {
					$data['id'] = $new_id;
				}
			}
			$new_data[] = $data;
		}

		return wp_parse_args( $new_data );
	}

	public function delete( WP_REST_Request $request ) {
		$id = (int) $request->get_url_params()['id'];
		if ( $id ) {
			$response = ConnectedSite::delete( $id );
			if ( $response ) {
				return wp_send_json_success();
			} else {
				new Error( 'Connected site was not deleted.' );

				return new WP_Error( 'database_error', 'DB Error: Connected site was not deleted.', array( 'status' => 501 ) );
			}
		} else {
			new Error( 'Connected site was not deleted. No ID present in URL.' );

			return new WP_Error( 'database_error', 'DB Error: Connected site was not deleted. No ID in URL.', array( 'status' => 501 ) );
		}
	}

	private function table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . ConnectedSite::$table_name;

		return in_array( $table_name, $wpdb->tables );
	}

}