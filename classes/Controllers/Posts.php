<?php


namespace DataSync\Controllers;

use WP_Query;

class Posts {

	public static function get( $types ) {

		$posts = Posts::get_posts( $types );

		foreach( $posts as $post ) {

			$post->post_meta = get_post_meta( $post->ID );

			$post->tags = wp_get_post_tags( $post->ID );

			$post->categories = get_the_terms( $post->ID, 'category' );


		}

		return $posts;

	}

	private static function get_posts( $types ) {
		$args = array(
			'post_type'       => $types,
			'post_status'     => array( 'publish' ),
			'orderby'         => 'post_date',
			'order'           => 'DESC',
			'posts_per_page'  => -1, // show all posts
		);

		$loop = new WP_Query( $args );

		return $loop->posts;
	}

}