<?php


namespace DataSync\Controllers;

use DataSync\Models\PostType;

class PostTypes {

	public function __construct() {
		$this->register();
	}

	public static function add_new_cpts( object $source_options ) {
		global $wp_post_types;
		$registered_receiver_cpts = (array) array_keys( $wp_post_types );

		foreach ( $source_options->push_enabled_post_types as $post_type => $post_type_data ) :
			if ( ! in_array( $post_type, $registered_receiver_cpts ) ) {
				self::save( $post_type_data );
			}
		endforeach;
	}

	public static function get( int $id ) {

	}

	public static function get_id( string $slug ) {

		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $table_name . ' WHERE name = %s', $slug ) );

	}

	private function get_all_cpts() {
		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return $wpdb->get_results( 'SELECT * FROM ' . $table_name );
	}

	private static function save( object $data ) {
		if ( ! self::table_exists() ) {
			PostType::create_db_table();
		}

		$existing_post_types = (array) self::get_id( $data->name );

		if ( count( $existing_post_types ) ) {
			foreach ( $existing_post_types as $post_type ) {
				$data->id = $post_type->id;
				PostType::update( $data );
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

	public static function save_options() {
		$data = (array) get_option( 'enabled_post_types' );

		$synced_custom_post_types = self::get_all_cpts();

		foreach( $synced_custom_post_types as $post_type ) {
			$data[] = $post_type->name;
		}

		update_option( 'enabled_post_types', $data );
	}

	private static function table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return in_array( $table_name, $wpdb->tables );
	}

	public function register() {

		$synced_custom_post_types = $this->get_all_cpts();
		foreach( $synced_custom_post_types as $post_type ) {
			$args = (array) json_decode( $post_type->data );
			print_r($args);
			register_post_type( $post_type->name, $args );
		}

	}

}