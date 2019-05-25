<?php


namespace DataSync\Controllers;

use DataSync\Models\PostType;

class PostTypes {

	public function __construct( object $source_options ) {

		global $wp_post_types;
		$registered_receiver_cpts = (array) array_keys( $wp_post_types );

		if ( (bool) $source_options->add_and_enable_new_cpts ) :
			foreach ( $source_options->push_enabled_post_types as $post_type => $post_type_data ) :
				if ( ! in_array( $post_type, $registered_receiver_cpts ) ) {
					$this->save( $post_type_data );
				}
			endforeach;
		endif;
	}

	public function get( int $id ) {

	}

	public function get_id( string $slug ) {

		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $table_name . ' WHERE name = %s', $slug ) );

	}

	private function get_all_cpts() {
		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return $wpdb->get_results( 'SELECT name FROM ' . $table_name );
	}

	private function save( object $data ) {
		if ( ! $this->table_exists() ) {
			PostType::create_db_table();
		}

		$existing_post_types = (array) $this->get_id( $data->name );

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

		$this->save_options();

		return wp_parse_args( $new_data );
	}

	private function save_options() {
		$data = (array) get_option( 'enabled_post_types' );

		$synced_custom_post_types = $this->get_all_cpts();

		foreach( $synced_custom_post_types as $post_type_slug ) {
			$data[] = $post_type_slug->name;
		}

		update_option( 'enabled_post_types', $data );
	}

	private function table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . PostType::$table_name;

		return in_array( $table_name, $wpdb->tables );
	}

}