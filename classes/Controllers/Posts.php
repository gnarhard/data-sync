<?php


namespace DataSync\Controllers;

use WP_Query;

class Posts {

	public static function get( $types ) {

		$posts = array();

		foreach ( $types as $type ) {

			$posts[ $type ] = Posts::get_posts( $type );

			foreach ( $posts[ $type ] as $post ) {

				$post->post_meta  = get_post_meta( $post->ID );
				$post->taxonomies = array();

				foreach ( get_taxonomies() as $taxonomy ) {
					$post->taxonomies[ $taxonomy ] = get_the_terms( $post->ID, $taxonomy );
				}

				$post->media = Posts::get_media( $post->ID );


			}
		}


		return $posts;

	}

	private static function get_posts( $type ) {
		$args = array(
			'post_type'      => $type,
			'post_status'    => array( 'publish' ),
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => - 1, // show all posts.
		);

		$loop = new WP_Query( $args );

		return $loop->posts;
	}

	private static function get_media( $post_id ) {
		return array(
			'image' => get_attached_media( 'image', $post_id ),
			'audio' => get_attached_media( 'audio', $post_id ),
			'video' => get_attached_media( 'video', $post_id ),
		);
	}

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

		return $json;

	}

	public static function create_post_type( $post_type_slug ) {

	}

	public static function process_post_types( $receiver_options, $source_options ) {

		global $wp_post_types;
		$registered_receiver_cpts = array_keys( $wp_post_types );

		if ( $receiver_options['add_and_enable_new_cpts'] ) {
			foreach( $source_options['push_enabled_post_types'] as $post_type ) {
				if ( ! in_array( $post_type, $registered_receiver_cpts ) ) {
					Posts::create_post_type( $post_type );
				}
			}
		}
	}

}