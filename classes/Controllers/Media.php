<?php


namespace DataSync\Controllers;


class Media {

	public function __construct( int $receiver_post_id, object $media, string $source_url ) {

		$image_attachments = (array) $media->image;
		$audio_attachments = (array) $media->audio;
		$video_attachments = (array) $media->video;

		foreach ( $image_attachments as $key => $image ) {
			$this->insert_into_wp( $receiver_post_id, $source_url, $image );
		}
		foreach ( $audio_attachments as $key => $audio ) {
			$this->insert_into_wp( $receiver_post_id, $source_url, $audio );
		}
		foreach ( $video_attachments as $key => $video ) {
			$this->insert_into_wp( $receiver_post_id, $source_url, $video );
		}

	}


	public function insert_into_wp( int $receiver_post_id, string $source_url, object $post ) {

		$post_array = (array) $post;

		$result = $this->copy_file( $source_url, $post_array['guid'] );

		if ( $result ) {
			$image['post_parent'] = $receiver_post_id;
			$image['guid']        = str_replace( $source_url, get_site_url(), $post_array['guid'] );
			$new_media_id         = wp_insert_post( $post_array );
		}
	}


	/**
	 * Transfer Files Server to Server using PHP Copy
	 *
	 */
	public function copy_file( string $source_url, string $remote_file_url ) {

		/* New file name and path for this file */
		$local_file = str_replace( $source_url, ABSPATH, $remote_file_url );

		/* Copy the file from source url to server */
		$copy = copy( $remote_file_url, $local_file );

		/* Add notice for success/failure */
		if ( ! $copy ) {
			echo "Failed to copy $remote_file_url \n";

			return false;
		} else {
			echo "Success copying $remote_file_url \n";

			return true;
		}

	}

}