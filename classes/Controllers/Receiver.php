<?php


namespace DataSync\Controllers;


use DataSync\Models\SyncedPost;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use DataSync\Controllers\Email;

class Receiver {

	public $response = '';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/receive',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'receive' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

	public function receive() {
		$source_data = (object) json_decode( file_get_contents( 'php://input' ) );

//		if ($source_data->debug) {
//			var_dump( 'Beginning to receive' );
//			print_r( $source_data );
//		}

		$this->parse( $source_data );

//		if ($source_data->debug) {
//			die();
//		}
	}

	private function parse( object $source_data ) {

		$receiver_options = (object) Options::receiver()->get_data();
		$receiver_site_id = (int) $source_data->receiver_site_id;
		update_option( 'data_sync_receiver_site_id', $receiver_site_id );
		update_option( 'data_sync_source_site_url', $source_data->url );
		update_option( 'debug', $source_data->debug );

		if ($receiver_site_id === 2) {
			echo 'hey';die();
		}

		$this->response = 'Site: ' . $receiver_site_id;

		PostTypes::process( $source_data->options->push_enabled_post_types );

		if ( $source_data->options->enable_new_cpts ) {
			PostTypes::save_options();
		}

//		echo 'finished syncing post types';
		new Logs( 'Finished syncing post types.' );

		foreach ( $source_data->custom_taxonomies as $taxonomy ) {
			Taxonomies::save( $taxonomy );
		}
		new Logs( 'Finished syncing custom taxonomies.' );

		foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {

			$post_count = count( $source_data->posts->$post_type_slug );

			if ( 0 === $post_count ) {
				new Logs( 'ERROR: No posts in data.', true );
			} else {
				foreach ( $source_data->posts->$post_type_slug as $post ) {
					$filtered_post = SyncedPosts::filter( $post, $receiver_site_id );

					if ( false !== $filtered_post ) {
						$receiver_post_id = Posts::save( $filtered_post );
						SyncedPosts::save( $receiver_post_id, $filtered_post );
					}
				}
			}

		}
		new Logs( 'Finished syncing posts.' );

	}

}