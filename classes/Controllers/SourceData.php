<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use WP_REST_Request;
use WP_REST_Server;
use ACF_Admin_Tool_Export;
use DataSync\Controllers\Error as Error;
use stdClass;

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
					'callback' => array( $this, 'push' ),
				),
			)
		);
	}

	public function push() {
		$source_data     = $this->consolidate();
		$connected_sites = $source_data->connected_sites;

		foreach ( $connected_sites as $site ) {

			$source_data->_receiver_site_id = (int) $site->id;
			$auth                           = new Auth();
			$json_decoded_data              = json_decode( wp_json_encode( $source_data ) ); // DO THIS TO MAKE SIGNATURE CONSISTENT. JSON DOESN'T RETAIN OBJECT CLASS TITLES
			$source_data->sig               = (string) $auth->create_signature( $json_decoded_data, $site->secret_key );
			$json                           = wp_json_encode( $source_data );
			$url                            = (string) trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
			$response                       = wp_remote_post( $url, [ 'body' => $json ] );
			$body                           = wp_remote_retrieve_body( $response );
			print_r( $body );
			die();

		}

	}

	private function consolidate() {

		$options = Options::source()->get_data();

		$source_data                  = new stdClass();
		$source_data->options         = (array) $options;
		$source_data->url             = (string) get_site_url();
		$source_data->connected_sites = (array) ConnectedSites::get_all()->get_data();
		$source_data->nonce           = (string) wp_create_nonce( 'data_push' );
		$source_data->posts           = (object) Posts::get( array_keys( $options->push_enabled_post_types ) );
		$source_data->acf             = (array) Posts::get_acf_fields(); // use acf_add_local_field_group() to install this array.

		return $this->validate( $source_data );


	}

	private function validate( object $source_data ) {

		foreach ( $source_data->posts as $post_type => $post_data ) {

			foreach ( $post_data as $key => $post ) {

				if ( ! isset( $post->post_meta['_canonical_site'] ) ) {
					unset( $source_data->posts->$post_type[ $key ] );
//					$error = new Error();
//					( $error ) ? $error->log( 'Canonical site not set in post: ' . $post->post_title . "\n" ) : null;
				}

				if ( ! isset( $post->post_meta['_excluded_sites'] ) ) {
					unset( $source_data->posts->$post_type[ $key ] );
//					$error = new Error();
//					( $error ) ? $error->log( 'Excluded sites not set in post: ' . $post->post_title . "\n" ) : null;
				}
			}
		}

		return $source_data;
	}

}