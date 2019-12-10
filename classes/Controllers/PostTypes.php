<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;
use DataSync\Models\PostType;
use DataSync\Controllers\Options;
use DataSync\Routes\PostTypesRoutes;
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
		new PostTypesRoutes( $this );
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
			$enabled_post_type_site_data[ $index ]                     = new stdClass();
			$enabled_post_type_site_data[ $index ]->site_id            = (int) $site->id;
			$enabled_post_type_site_data[ $index ]->enabled_post_types = self::check_enabled_post_types_on_receiver( $site );
			$index ++;
		}

		return $enabled_post_type_site_data;
	}


	public static function check_enabled_post_types_on_receiver( object $site ) {
		$url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/post_types/check';
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$logs = new Logs();
			$logs->set( 'Error in PostTypes->check_enabled_post_types_on_receiver() received from ' . $site->url . '. ' . $response->get_error_message(), true );

			return false;
		} else {
			return json_decode( wp_remote_retrieve_body( $response ) );
		}
	}

	public function get_enabled_post_types() {
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
	public static function save( object $data ) {
		$existing_post_types = (array) self::get_id_from_slug( $data->name );

		if ( count( $existing_post_types ) ) {
			foreach ( $existing_post_types as $post_type ) {
				$data->id = $post_type->id;
				$return   = PostType::update( $data );
				if ( is_wp_error( $return ) ) {
					$logs = new Logs();
					$logs->set( 'Post type was not updated.' . '<br>' . $return->get_error_message(), true );

					return $return;
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
		$enabled_post_types = get_option( 'enabled_post_types' );
		if ( false === $enabled_post_types ) {
			$enabled_post_types = [];
		}
		$synced_custom_post_types        = PostType::get_all();
		$synced_custom_post_types_to_add = array();
		$registered_custom_post_types    = cptui_get_post_type_data();
		$registered_post_types           = get_post_types( [
			'public' => true,
		], 'names', 'and' );

		// Merge registered default and custom post types together.
		foreach ( $registered_custom_post_types as $registered_custom_post_type ) {
			$registered_post_types[] = $registered_custom_post_type['name'];
		}

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
		$cleaned_post_types  = $unique_post_types;

		foreach ( $unique_post_types as $key => $post_type ) {
			// If post type is already registered, but not enabled,
			// don't allow it to become enabled.
			if ( ( in_array( $post_type, $registered_post_types ) ) && ( ! in_array( $post_type, $enabled_post_types ) ) ) {
				unset( $cleaned_post_types[ $key ] );
			}
		}

		update_option( 'enabled_post_types', $cleaned_post_types );
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

}
