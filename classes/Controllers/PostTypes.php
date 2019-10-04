<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;
use DataSync\Models\PostType;
use DataSync\Controllers\Options;
use WP_REST_Server;
use stdClass;

/**
 * Class PostTypes
 * @package DataSync\Controllers
 */
class PostTypes {

	/**
	 * PostTypes constructor.
	 * Registers all CTPs
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * @param string $slug
	 *
	 * @return mixed
	 */
	public static function get_id_from_slug( string $slug ) {
		$args = [ 'name' => $slug ];

		return PostType::get_where( $args );
	}


	public static function get_all_enabled_post_types_from_receivers( $connected_sites ) {
		$index                       = 0;
		$enabled_post_type_site_data = array();

		foreach ( $connected_sites as $site ) {
			$enabled_post_type_site_data[ $index ] = new stdClass();
			$enabled_post_type_site_data[ $index ]->site_id            = (int) $site->id;
			$enabled_post_type_site_data[ $index ]->enabled_post_types = self::check_enabled_post_types_on_receiver( $site );
			$index++;
		}

		return $enabled_post_type_site_data;
	}


	public static function check_enabled_post_types_on_receiver( object $site ) {

		$url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/post_types/check';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
			$log = new Logs( 'Error in PostTypes->check_enabled_post_types_on_receiver() received from ' . $site->url . '. ' . $response->get_error_message(), true );
			unset( $log );

			return false;
		} else {
			return json_decode( wp_remote_retrieve_body( $response ) );
		}
	}

	public function check_enabled_post_types() {
		return Options::receiver()->enabled_post_types;
	}

	/**
	 * Saves all Custom Post Types to database table
	 * DB Table: data_sync_custom_post_types
	 *
	 * @param object $post_types
	 */
	public static function process( object $post_types ) {
		foreach ( $post_types as $post_type => $post_type_data ) :

			if ( 'post' === $post_type ) {
				continue;
			}

			self::save( $post_type_data );

		endforeach;
	}


	/**
	 * @param object $data
	 *
	 * Saves all custom post types to database
	 * DB Table Name: data_sync_custom_post_types
	 *
	 * @return mixed
	 */
	static function save( object $data ) {

		$existing_post_types = (array) self::get_id_from_slug( $data->name );

		if ( count( $existing_post_types ) ) {
			foreach ( $existing_post_types as $post_type ) {
				$data->id = $post_type->id;
				$return   = PostType::update( $data );
				if ( is_wp_error( $return ) ) {
					$log = new Logs( 'Post type was not updated.' . '<br>' . $return->get_error_message(), true );
					unset( $log );
				}
			}
		} else {
			$new_id = PostType::create( $data );
			if ( is_numeric( $new_id ) ) {
				$data->id = $new_id;
			}
		}

		$new_data[] = $data;

		return wp_parse_args( $new_data );
	}

	/**
	 *
	 * Saves enabled custom post types for plugin option
	 *
	 */
	public static function save_options() {
		$enabled_post_types              = (array) get_option( 'enabled_post_types' );
		$synced_custom_post_types        = PostType::get_all();
		$synced_custom_post_types_to_add = array();

		foreach ( $synced_custom_post_types as $cpt ) {
			if ( '' !== $cpt->name ) {
				$synced_custom_post_types_to_add[] = $cpt->name;
			}
		}

		foreach ( $enabled_post_types as $key => $enabled_post_type ) {
			if ( '' === $enabled_post_type ) {
				unset( $enabled_post_types[ $key ] );
			}
		}

		$merged_post_types   = array_merge( $enabled_post_types, $synced_custom_post_types_to_add );
		$merged_post_types[] = 'post'; // NOT INCLUDED IF IT'S A BRAND NEW RECEIVER SITE, THEY HAVEN'T ENABLED ANY POST TYPES, AND THE SETTING TO OVERWRITE ENABLED POST TYPES WAS SET.
		$unique_post_types   = array_unique( $merged_post_types );

		update_option( 'enabled_post_types', $unique_post_types );
	}

	/**
	 * Registers CTPs on plugin load
	 *
	 */
	public function register() {

		$synced_custom_post_types = PostType::get_all();

		foreach ( $synced_custom_post_types as $post_type ) {
			$args = (array) json_decode( $post_type->data );

			foreach ( $args as $key => $value ) {
				if ( ( 'true' === $value ) || ( 'false' === $value ) ) {
					$args[ $key ] = ( 'true' === $value );
				}
			}

			$args['labels']    = array( 'menu_name' => $args['label'] );
			$args['menu_icon'] = ( '' === $args['menu_icon'] ) ? 'dashicons-admin-post' : $args['menu_icon'];

			$result = register_post_type( $post_type->name, $args );
		}

	}

	public function register_routes() {

		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/post_types/check',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'check_enabled_post_types' ),
				),
			)
		);

	}

}