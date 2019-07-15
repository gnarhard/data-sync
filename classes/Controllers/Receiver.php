<?php


namespace DataSync\Controllers;


use DataSync\Controllers\Email;
use DataSync\Models\SyncedPost;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use DataSync\Models\DB;

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
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/start_fresh',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'start_fresh' ),
				),
			)
		);
	}

	public function start_fresh() {

		$db               = new DB();
		$sql_statements   = array();
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_custom_post_types';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_custom_taxonomies';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_log';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_posts';
		$sql_statements[] = 'TRUNCATE TABLE wp_posts';
		$sql_statements[] = 'TRUNCATE TABLE wp_postmeta';

		foreach ( $sql_statements as $sql ) {
			$db->query( $sql );
		}

		wp_send_json_success( 'Receiver table truncation completed.' );

	}

	public function receive() {
		$source_data = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->parse( $source_data );
//		$email = new Email();
//		unset( $email );

		$log = new Logs( 'SYNC COMPLETE.' );
		unset( $log );

		wp_send_json_success( 'Receiver parse complete.' );
	}

	private function parse( object $source_data ) {

		if ( $source_data->single_overwrite ) {
			// OVERWRITE SINGLE POST.
			$filtered_post = SyncedPosts::filter( $source_data->post, $source_data->options, $source_data->synced_posts );

			if ( false !== $filtered_post ) {
				$receiver_post_id = Posts::save( $filtered_post, $source_data->synced_posts );
				SyncedPosts::save_to_receiver( $receiver_post_id, $filtered_post );

				$log = new Logs( 'Finished syncing: ' . $filtered_post->post_title . ' (' . $filtered_post->post_type . ').' );
				unset( $log );
			}

		} else {
			// DO BULK UPLOAD.

			$receiver_options = (object) Options::receiver()->get_data();

			update_option( 'data_sync_receiver_site_id', (int) $source_data->receiver_site_id );
			update_option( 'data_sync_source_site_url', $source_data->url );
			update_option( 'debug', $source_data->options->debug );
			update_option( 'show_body_responses', $source_data->options->show_body_responses );
			update_option( 'overwrite_receiver_post_on_conflict', $source_data->options->overwrite_receiver_post_on_conflict );

			PostTypes::process( $source_data->options->push_enabled_post_types );
			if ( true === $source_data->options->enable_new_cpts ) {
				PostTypes::save_options();
			}
			$log = new Logs( 'Finished syncing post types.' );
			unset( $log );

			foreach ( $source_data->custom_taxonomies as $taxonomy ) {
				Taxonomies::save( $taxonomy );
			}

			$log = new Logs( 'Finished syncing custom taxonomies.' );
			unset( $log );

			foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {

				$post_count = count( $source_data->posts->$post_type_slug );

				if ( 0 === $post_count ) {
					$log = new Logs( 'No posts in data.', true );
				} else {
					foreach ( $source_data->posts->$post_type_slug as $post ) {
						$filtered_post = SyncedPosts::filter( $post, $source_data->options, $source_data->synced_posts );

						if ( false !== $filtered_post ) {
							$receiver_post_id = Posts::save( $filtered_post, $source_data->synced_posts );
							SyncedPosts::save_to_receiver( $receiver_post_id, $filtered_post );

							$log = new Logs( 'Finished syncing: ' . $filtered_post->post_title . ' (' . $filtered_post->post_type . ').' );
							unset( $log );
						}
					}
				}
			}
		}

	}

}