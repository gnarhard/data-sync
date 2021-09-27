<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;
use DataSync\Models\PostType;
use DataSync\Controllers\Options;
use DataSync\Routes\PostTypesRoutes;
use DataSync\Controllers\ConnectedSites;
use stdClass;

/**
 * Class PostTypes
 *
 * @package DataSync\Controllers
 */
class PostTypes {

	/**
	 * PostTypes constructor.
	 * Registers all CTPs
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		new PostTypesRoutes( $this );
	}

	/**
	 * @param string $slug
	 *
	 * @return mixed
	 */
	public static function get_id_from_slug( string $slug ) {
		$args = array( 'name' => $slug );

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
		$site = ConnectedSites::get_api_url($site);
		$url      = $site->api_url . DATA_SYNC_API_BASE_URL . '/post_types/check';
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
	 */
	public static function save_options( $push_enabled_post_types_from_source, $enable_new_cpts ) {

		if (function_exists('cptui_get_post_type_data')) {
			$enabled_post_types              = ( false !== get_option( 'enabled_post_types' ) ) ? get_option( 'enabled_post_types' ) : array();
			$synced_custom_post_types        = PostType::get_all();
			$synced_custom_post_types_to_add = array();
			$registered_custom_post_types    = cptui_get_post_type_data();
			$registered_post_types           = get_post_types(
				array(
					'public' => true,
				),
				'names',
				'and'
			);
			$push_enabled_post_types         = array();
			foreach ( (array) $push_enabled_post_types_from_source as $post_type => $post_type_data ) {
				$push_enabled_post_types[] = $post_type;
			}

			// Merge registered default and custom post types together.
			foreach ( $registered_custom_post_types as $registered_custom_post_type ) {
				$registered_post_types[] = $registered_custom_post_type['name'];
			}

			// Consolidate synced custom post types that may or may not have been registered.
			foreach ( $synced_custom_post_types as $cpt ) {
				if ( '' !== $cpt->name ) {
					$synced_custom_post_types_to_add[] = $cpt->name;
				}
			}

			// Get all currently enabled post types on receiver.
			foreach ( $enabled_post_types as $key => $enabled_post_type ) {
				if ( '' === $enabled_post_type ) {
					unset( $enabled_post_types[ $key ] );
				}
			}

			// All post types that have eligibility to be enabled.
			$available_post_types       = array_merge( $synced_custom_post_types_to_add, $registered_post_types );
			$available_post_types       = array_merge( $push_enabled_post_types, $available_post_types );
			$available_post_types       = array_unique( $available_post_types );
			$updated_enabled_post_types = $enabled_post_types;

			foreach ( $available_post_types as $available_cpt ) {

				if ( ( in_array( $available_cpt, $synced_custom_post_types_to_add ) ) && ( in_array( $available_cpt, $registered_post_types ) ) && ( in_array( $available_cpt, $enabled_post_types ) ) ) {
					// If post type is registered and synced and already in enabled posts, either ignore it, or add it back in without redundancy.
					$updated_enabled_post_types[] = $available_cpt;
				}

				if ( ( ! in_array( $available_cpt, $synced_custom_post_types_to_add ) ) && ( $enable_new_cpts ) ) {
					// If post type isn't synced and the option to auto enable on first push is on, add to enabled post types.
					if ( 'post' !== $available_cpt ) {
						$updated_enabled_post_types[] = $available_cpt;
					}
				}

				if ( ( in_array( $available_cpt, $synced_custom_post_types_to_add ) ) && ( in_array( $available_cpt, $registered_post_types ) ) && ( ! in_array( $available_cpt, $enabled_post_types ) ) ) {
					// If post type is synced or registered but not enabled, remove it from enabled post types.
					foreach ( $updated_enabled_post_types as $key => $ept ) {
						if ( $available_cpt === $ept ) {
							unset( $updated_enabled_post_types[ $key ] );
						}
					}
				}

				if ( ( ! in_array( $available_cpt, $synced_custom_post_types_to_add ) ) && ( ! $enable_new_cpts ) && ( ! in_array( $available_cpt, $enabled_post_types ) ) ) {
					// If post type isn't synced and the option to auto enable on first push is off, remove it from enabled post types.
					foreach ( $updated_enabled_post_types as $key => $ept ) {
						if ( $available_cpt === $ept ) {
							unset( $updated_enabled_post_types[ $key ] );
						}
					}
				}
			}

			$unique_updated_enabled_post_types = array_unique( $updated_enabled_post_types );
			foreach ( $unique_updated_enabled_post_types as $key => $pt ) {
				if ( 'attachment' === $pt ) {
					unset( $unique_updated_enabled_post_types[ $key ] );
				} elseif ( 'page' === $pt ) {
					unset( $unique_updated_enabled_post_types[ $key ] );
				}
			}

			update_option( 'enabled_post_types', $unique_updated_enabled_post_types );
		}

	}

	/**
	 * Registers CTPs on plugin load
	 */
	public function register() {
		$synced_custom_post_types = PostType::get_all();
		$enabled_post_types       = ( false !== get_option( 'enabled_post_types' ) ) ? get_option( 'enabled_post_types' ) : array();

		foreach ( $synced_custom_post_types as $post_type ) {
			$args = (array) json_decode( $post_type->data );

			foreach ( $args as $key => $value ) {
				if ( ( 'true' === $value ) || ( 'false' === $value ) ) {
					$args[ $key ] = ( 'true' === $value );
				}
			}

			$args['labels']    = array( 'menu_name' => $args['label'] );
			$args['menu_icon'] = ( '' === $args['menu_icon'] ) ? 'dashicons-admin-post' : $args['menu_icon'];

			if ( in_array( $post_type->name, $enabled_post_types ) ) {
				$result = register_post_type( $post_type->name, $args );
			}
		}
	}

}
