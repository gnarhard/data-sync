<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;

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

		$post_array      = (array) $post;
		$receiver_url    = $post_array['guid'];
		$upload_dir      = wp_get_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$result = File::copy( $source_url, $receiver_url );

		if ( $result ) {
			$media['post_parent'] = $receiver_post_id;
			$media['guid']        = str_replace( $source_url, get_site_url(), $post_array['guid'] );

			// TODO: CREATED ANOTHER CURL ERROR. FIX BY MAKING THIS IT'S OWN cURL REQUEST.
			$file_path_exploded = explode( '/uploads', $media['guid'] );
			$file_path          = $upload_base_dir . $file_path_exploded[1];
			$filename           = basename( $file_path );
			$wp_filetype        = wp_check_filetype( $filename, null );

			$attachment    = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent'    => $media['post_parent'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $file_path, $media['post_parent'] );// TODO: THIS IS CREATING DUPLICATE MEDIA ITEMS. CHECK IN DB FOR IT FIRST AND MAKE SURE IT'S ADDING TO SYNC TABLE.

			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}
		}
	}

	public function delete() {
		// TODO: PROCESS DELETING A MEDIA ITEM
	}

}