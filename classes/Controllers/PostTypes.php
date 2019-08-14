<?php


namespace DataSync\Controllers;

use DataSync\Models\PostType;
use DataSync\Controllers\Logs;

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
		$data = (array) get_option( 'enabled_post_types' );

		$synced_custom_post_types = PostType::get_all();

		foreach ( $synced_custom_post_types as $post_type ) {
			$data[] = $post_type->name;
		}

		update_option( 'enabled_post_types', $data );
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

			$args['labels'] = array( 'menu_name' => $args['label'] );
			$args['menu_icon'] = ( '' === $args['menu_icon'] ) ? 'dashicons-admin-post' : $args['menu_icon'];

			$result = register_post_type( $post_type->name, $args );
		}

	}

}