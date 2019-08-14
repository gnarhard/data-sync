<?php


namespace DataSync\Controllers;

use DataSync\Helpers;
use WP_Query;


class ACF {

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

		foreach ( $acf_data as $field_group ) {
			$acf_group_data_array = Helpers::object_to_array( $field_group );
			$result = acf_import_field_group( $acf_group_data_array );
		}

	}

}