<?php


namespace DataSync\Controllers;

use DataSync\Helpers;
use WP_Query;
use stdClass;
use DataSync\Models\ACF;


class ACFs {

	public static function get_acf_fields() {
		$args = array(
			'post_type'      => 'acf-field-group',
			'post_status'    => array( 'publish' ),
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => - 1, // show all posts.
		);

		$loop = new WP_Query( $args );

		$acf_groups = $loop->posts;

		$field_group = array();

		foreach ( $acf_groups as $field ) {

			$key = $field->post_name;

			// load field group.
			$field_group = acf_get_field_group( $key );

			// validate field group.
			if ( empty( $field_group ) ) {
				continue;
			}

			// load fields.
			$field_group['fields'] = acf_get_fields( $field_group );

			// prepare for export.
			$field_group = acf_prepare_field_group_for_export( $field_group );

			// add to json array.
			$json[] = $field_group;

		}

		if ( isset( $json ) ) {
			return $json;
		} else {
			return array();
		}


	}


	public static function save_acf_fields( $acf_data ) {


		$acf_group_data_array = Helpers::object_to_array( $acf_data );

//		$synced_acf_groups = ACF::get_all();
//
//		foreach ( $synced_acf_groups as $synced_group ) {
//			foreach ( $acf_data as $source_field_group ) {
//				if ( $synced_group->name === $source_field_group['title'] ) {
//					$source_field_group['ID']        = (int) $synced_group->receiver_id;
//					$source_field_group['synced_id'] = (int) $synced_group->id;
//				}
//			}
//		}


		foreach ( $acf_group_data_array as $source_field_group ) {

			$existing_acf_group = get_page_by_path( $source_field_group['key'], ARRAY_A, 'acf-field-group' );

			if ( count( $existing_acf_group ) ) {
				$source_field_group['ID'] = $existing_acf_group['ID'];
			}

			$result = acf_import_field_group( $source_field_group );

//			if ( count( $result ) ) {
//				self::save_to_sync_table( $result );
//			}
		}

	}


	public static function save_to_sync_table( $saved_group ) {

		$data              = new \stdClass();
		$data->name        = $saved_group['title'];
		$data->receiver_id = $saved_group['ID'];

		if ( isset( $saved_group['synced_id'] ) ) {
			$data->id = (int) $saved_group['synced_id'];
			$result   = ACF::update( $data );
		} else {
			$result = ACF::create( $data );
		}

	}

}