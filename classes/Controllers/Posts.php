<?php


namespace DataSync\Controllers;

use WP_Query;

class Posts {

	public $table_name = 'data_sync_post_types';

	public static function get( $types ) {
		$posts = array();

		foreach ( $types as $type ) {

			$posts[ $type ] = self::get_posts( $type );

			foreach ( $posts[ $type ] as $post ) {

				$post->source_url = get_site_url();
				$post->post_meta  = get_post_meta( $post->ID );
				$post->taxonomies = array();

				foreach ( get_taxonomies() as $taxonomy ) {
					$post->taxonomies[ $taxonomy ] = get_the_terms( $post->ID, $taxonomy );
				}

				$post->media = self::get_media( $post->ID );


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

	private static function save( object $post ) {

		$post_id = $post->ID;
		$post_meta = $post->post_meta;
		$taxonomies = $post->taxonomies;
		$media = $post->media;
		$post_array = (array) $post; // must convert to array to use wp_insert_post.

		// MUST UNSET ID TO INSERT. PROVIDE ID TO UPDATE
		unset($post_array['ID']);

		foreach( $post_array as $key => $value ) {
			str_replace( $post->source_url, get_site_url(), $value );
		}

		print_r($post_array);
		$post_id = wp_insert_post( $post_array );
		var_dump($post_id);
		die();
	}


	public static function sync( array $post_data) {

		foreach( $post_data as $post ) {
			Posts::save( $post );
		}

	}



}