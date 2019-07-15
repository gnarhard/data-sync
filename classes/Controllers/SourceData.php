<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;
use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use WP_REST_Request;
use WP_REST_Server;
use ACF_Admin_Tool_Export;
use stdClass;
use DataSync\Models\DB;

class SourceData {

	public $source_data;
	public $receiver_logs;
	public $receiver_synced_posts;

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
					'callback' => array( $this, 'push' ),
				),
			)
		);

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/source_data/start_fresh',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'start_fresh' ),
				),
			)
		);
	}

	public function push() {

		// TODO: Create interface for pushing single post and overwriting the receiver when the receiver post was updated more recently.

		$this->consolidate();
		$this->validate();

		foreach ( $this->source_data->connected_sites as $site ) {

			$this->source_data->receiver_site_id = (int) $site->id;
			$auth                                = new Auth();
			$json                                = $auth->prepare( $this->source_data, $site->secret_key );
			$url                                 = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
			$response                            = wp_remote_post( $url, [ 'body' => $json ] );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in SourceData->push() received from ' . $site->url . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
				if ( get_option( 'show_body_responses' ) ) {
					if ( get_option( 'show_body_responses' ) ) {
						print_r( wp_remote_retrieve_body( $response ) );
					}
				}
			}

		}

		$this->get_receiver_data();
		$this->save_receiver_data();

		new Media( $this->source_data->posts );

		wp_send_json_success( 'Push complete.' );

	}

	public function start_fresh() {

		$db               = new DB();
		$connected_sites  = (array) ConnectedSites::get_all()->get_data();
		$sql_statements   = array();
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_posts';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_log';

		foreach ( $sql_statements as $sql ) {
			$db->query( $sql );
		}

		foreach ( $connected_sites as $site ) {

			$url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/start_fresh';
			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in SourceData->push() received from ' . $site->url . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
				if ( get_option( 'show_body_responses' ) ) {
					if ( get_option( 'show_body_responses' ) ) {
						print_r( wp_remote_retrieve_body( $response ) );
					}
				}
			}

		}

		wp_send_json_success( 'Source table truncation completed.' );

	}

	private function get_receiver_data() {
		$this->receiver_logs         = Logs::retrieve_receiver_logs( $this->source_data->start_time );
		$this->receiver_synced_posts = SyncedPosts::retrieve_from_receiver( $this->source_data->start_time );
	}

	private function save_receiver_data() {
		Logs::save_to_source( $this->receiver_logs );
		SyncedPosts::save_all_to_source( $this->receiver_synced_posts );
	}

	private function consolidate() {

		$synced_posts = new SyncedPosts();

		update_option( 'show_body_responses', true );
		$options = Options::source()->get_data();

		$this->source_data                    = new stdClass();
		$this->source_data->start_time        = (string) current_time( 'mysql' );
		$this->source_data->start_microtime   = (float) microtime( true );
		$this->source_data->options           = (array) $options;
		$this->source_data->acf               = (array) Posts::get_acf_fields(); // use acf_add_local_field_group() to install this array.
		$this->source_data->custom_taxonomies = (array) cptui_get_taxonomy_data();
		$this->source_data->url               = (string) get_site_url();
		$this->source_data->connected_sites   = (array) ConnectedSites::get_all()->get_data();
		$this->source_data->nonce             = (string) wp_create_nonce( 'data_push' );
		$this->source_data->posts             = (object) Posts::get( array_keys( $options->push_enabled_post_types ) );
		$this->source_data->synced_posts      = (array) $synced_posts->get_all()->get_data();

	}

	private function validate() {

		foreach ( $this->source_data->posts as $post_type => $post_data ) {

			foreach ( $post_data as $key => $post ) {

				if ( ! isset( $post->post_meta['_canonical_site'] ) ) {
					unset( $this->source_data->posts->$post_type[ $key ] );
					$log = new Logs( 'SKIPPING: Canonical site not set in post: ' . $post->post_title, true );
					unset( $log );
				}

				if ( ! isset( $post->post_meta['_excluded_sites'] ) ) {
					unset( $this->source_data->posts->$post_type[ $key ] );
					$log = new Logs( 'SKIPPING: Excluded sites not set in post: ' . $post->post_title, true );
					unset( $log );
				}
			}
		}

	}

}