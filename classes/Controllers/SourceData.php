<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use WP_REST_Server;
use ACF_Admin_Tool_Export;

class SourceData {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/source_data/push',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get' ),
//					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
				)
			)
		);
	}

	public function get() {

		$options         = Options::get_all()->data;
		$connected_sites = ConnectedSites::get_all()->data;
		$posts           = Posts::get( $options['push_enabled_post_types'] ); // Includes post type, post_meta (includes ACF data), tags, taxonomies, media.
		$acf_fields      = Posts::get_acf_fields();

		$source_data = array(
			'source' => array(
				'options'         => $options,
				'connected_sites' => $connected_sites,
			),
			'posts'  => $posts,
			'acf'    => $acf_fields, // use acf_add_local_field_group() to install this array.
		);

		print_r( $source_data );

	}

}