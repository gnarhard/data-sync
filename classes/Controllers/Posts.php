<?php


namespace DataSync\Controllers;

use WP_Query;

class Posts {

	public static function get( $types ) {

		$posts = array();

		print_r( get_taxonomies() );

		foreach ( $types as $type ) {

			$posts[ $type ] = Posts::get_posts( $type );

			foreach ( $posts[ $type ] as $post ) {

				$post->post_meta = get_post_meta( $post->ID );

//				$post->tags = wp_get_post_tags( $post->ID );
				$post->taxonomies = array();

				foreach ( get_taxonomies() as $taxonomy ) {
					$post->taxonomies[$taxonomy] = get_the_terms( $post->ID, $taxonomy );
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

}