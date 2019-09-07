<?php


namespace DataSync\Controllers;


use DataSync\Controllers\Email;
use DataSync\Models\SyncedPost;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use DataSync\Models\DB;

/**
 * Class Receiver
 * @package DataSync\Controllers
 */
class Receiver {

	/**
	 * @var string
	 */
	public $response = '';

	/**
	 * Receiver constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 *
	 */
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

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/plugin_versions',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_plugin_versions' ),
				),
			)
		);
	}

	/**
	 *
	 */
	public function start_fresh() {

		$db               = new DB();
		$sql_statements   = array();
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_custom_post_types';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_custom_taxonomies';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_log';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_posts';
		$sql_statements[] = 'TRUNCATE TABLE wp_data_sync_terms';

		$sql_statements[] = 'TRUNCATE TABLE wp_posts';
		$sql_statements[] = 'TRUNCATE TABLE wp_postmeta';
		$sql_statements[] = 'TRUNCATE TABLE wp_terms';
		$sql_statements[] = 'TRUNCATE TABLE wp_termmeta';
		$sql_statements[] = 'TRUNCATE TABLE wp_term_taxonomy';
		$sql_statements[] = 'TRUNCATE TABLE wp_term_relationships';

		foreach ( $sql_statements as $sql ) {
			$db->query( $sql );
		}

		wp_send_json_success( 'Receiver table truncation completed.' );

	}


	/**
	 * Source side that initiates request for receiver plugin versions.
	 */
	public static function get_receiver_plugin_versions() {

		$connected_sites = (array) ConnectedSites::get_all()->get_data();

		$plugin_versions = array();

		foreach ( $connected_sites as $site ) {

			$url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/plugin_versions';
			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in Receiver->get_receiver_plugin_versions() received from ' . $site->url . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
//				if ( get_option( 'show_body_responses' ) ) {
//					if ( get_option( 'show_body_responses' ) ) {
//						echo 'Receiver';
//						var_dump( wp_remote_retrieve_body( $response ) );
//					}
//				}

				$plugin_versions[] = [
					'site_id' => $site->id,
					'versions' => json_decode( wp_remote_retrieve_body( $response ) )->data,
				];

			}

		}

		return $plugin_versions;
	}

	/**
	 * @return mixed
	 */
	public function get_plugin_versions() {
		$plugins = get_plugins();

		$versions          = array();
		$versions['acf']   = $plugins['advanced-custom-fields-pro/acf.php']['Version'];
		$versions['cptui'] = $plugins['custom-post-type-ui/custom-post-type-ui.php']['Version'];

		return wp_send_json_success( $versions );

	}

	/**
	 *
	 */
	public function receive() {
		$source_data = (object) json_decode( file_get_contents( 'php://input' ) );

		$this->process( $source_data );

//		$email = new Email();
//		unset( $email );

		$log = new Logs( 'SYNC COMPLETE.' );
		unset( $log );

		wp_send_json_success( 'Receiver parse complete.' );
	}

	/**
	 * @param object $source_data
	 */
	private function process( object $source_data ) {

		// STEP 1: GET ALL CUSTOM RECEIVER OPTIONS THAT WOULD BE IN THE PLUGIN SETTINGS.
		$receiver_options = (object) Options::receiver()->get_data();

		// STEP 2: UPDATE LOCAL OPTIONS WITH FRESH SOURCE OPTION DATA.
		update_option( 'data_sync_receiver_site_id', (int) $source_data->receiver_site_id );
		update_option( 'data_sync_source_site_url', $source_data->url );
		update_option( 'debug', $source_data->options->debug );
		update_option( 'show_body_responses', $source_data->options->show_body_responses );
		update_option( 'overwrite_receiver_post_on_conflict', (bool) $source_data->options->overwrite_receiver_post_on_conflict );

		// STEP 3: ADD ALL CUSTOM POST TYPES AND CHECK IF THEY ARE ENABLED BY DEFAULT.
		// IF SO, SAVE THE OPTIONS, IF NOT, MOVE ON.
		PostTypes::process( $source_data->options->push_enabled_post_types );
		if ( true === $source_data->options->enable_new_cpts ) {
			PostTypes::save_options();
		}
		$log = new Logs( 'Finished syncing post types.' );
		unset( $log );

		// STEP 4: ADD AND SAVE ACF FIELDS
		ACFs::save_acf_fields( $source_data->acf );
		$log = new Logs( 'Finished syncing ACF fields.' );
		unset( $log );

		// STEP 5: ADD AND SAVE ALL TAXONOMIES.
		foreach ( $source_data->custom_taxonomies as $taxonomy ) {
			SyncedTaxonomies::save( $taxonomy );
		}
		$log = new Logs( 'Finished syncing custom taxonomies.' );
		unset( $log );

		// SAFEGUARD AGAINST SITES WITHOUT ANY ENABLED POST TYPES.
		if ( 'string' !== gettype( $receiver_options->enabled_post_types ) ) {

			// STEP 6: START PROCESSING ALL POSTS THAT ARE INCLUDED IN RECEIVER'S ENABLED POST TYPES.
			foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {

				if ( ! isset( $source_data->posts->$post_type_slug ) ) {
					continue; // SKIPS EMPTY DATA.
				}

				$post_count = count( $source_data->posts->$post_type_slug );

				if ( 0 === $post_count ) {
					$log = new Logs( 'No posts in source data.', true );
					unset( $log );
				} else {
					// LOOP THROUGH ALL POSTS THAT ARE IN A SPECIFIC POST TYPE.
					foreach ( $source_data->posts->$post_type_slug as $post ) {

						// FILTER OUT POSTS THAT SHOULDN'T BE SYNCED.
						$filtered_post = SyncedPosts::filter( $post, $source_data->options, $source_data->synced_posts );

						if ( false !== $filtered_post ) {
							$receiver_post_id = Posts::save( $filtered_post, $source_data->synced_posts );

							$synced_post_result = SyncedPosts::save_to_receiver( $receiver_post_id, $filtered_post );

							$log = new Logs( 'Finished syncing: ' . $filtered_post->post_title . ' (' . $filtered_post->post_type . ').' );
							unset( $log );
						}
					}
				}
			}
		}

	}

}