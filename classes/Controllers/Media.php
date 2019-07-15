<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;
use DataSync\Models\DB;
use DataSync\Models\SyncedPost;
use WP_REST_Server;

class Media {

	public function __construct( $all_posts = null ) {

		if ( null === $all_posts ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {

			$connected_sites = (array) ConnectedSites::get_all()->get_data();
			$synced_posts    = new SyncedPosts();
			$synced_posts    = (array) $synced_posts->get_all()->get_data();

			$media = array();

			foreach ( $all_posts as $post_type ) {
				foreach ( $post_type as $post ) {

					$image_attachments = (array) $post->media->image;
					foreach ( $image_attachments as $key => $image ) {
						foreach ( $synced_posts as $synced_post ) {
							if ( (int) $image->post_parent === (int) $synced_post->source_post_id ) {
								$image->receiver_post_id = $synced_post->receiver_post_id;
							}
						}
						$this->send_to_receiver( $image, $connected_sites );
					}

					$audio_attachments = (array) $post->media->audio;
					foreach ( $audio_attachments as $key => $audio ) {
						foreach ( $synced_posts as $synced_post ) {
							if ( (int) $audio->post_parent === (int) $synced_post->source_post_id ) {
								$audio->receiver_post_id = $synced_post->receiver_post_id;
							}
						}
						$this->send_to_receiver( $audio, $connected_sites );
					}

					$video_attachments = (array) $post->media->video;
					foreach ( $video_attachments as $key => $video ) {
						foreach ( $synced_posts as $synced_post ) {
							if ( (int) $video->post_parent === (int) $synced_post->source_post_id ) {
								$video->receiver_post_id = $synced_post->receiver_post_id;
							}
						}
						$this->send_to_receiver( $video, $connected_sites );
					}
				}
			}

		}

	}

	public function send_to_receiver( $media, $connected_sites ) {

		$synced_posts       = new SyncedPosts();
		$data               = new \stdClass();
		$data->media        = $media;
		$data->source_url   = get_site_url();
		$data->synced_posts = (array) $synced_posts->get_all()->get_data();

		foreach ( $connected_sites as $site ) {

			$data->receiver_site_id = (int) $site->id;
			$auth                   = new Auth();
			$json                   = $auth->prepare( $data, $site->secret_key );
			$url                    = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/media/update';
			$response               = wp_remote_post( $url, [ 'body' => $json ] );

			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				$log = new Logs( 'Error in Media->update() received from ' . $site->url . '. ' . $response->get_error_message(), true );
				unset( $log );
			} else {
				if ( get_option( 'show_body_responses' ) ) {
					if ( get_option( 'show_body_responses' ) ) {
						print_r( wp_remote_retrieve_body( $response ) );
					}
				}
			}

		}

	}

	public function update() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$this->insert_into_wp( $data->source_url, $data->media, $data->synced_posts );
		wp_send_json_success( $data->media );
	}


	public function insert_into_wp( string $source_url, object $post, array $synced_posts ) {

		$post_array      = (array) $post;
		$receiver_url    = $post_array['guid'];
		$upload_dir      = wp_get_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$result = File::copy( $source_url, $receiver_url );

		if ( $result ) {
			$media['post_parent'] = (int) $post->receiver_post_id;
			$media['guid']        = (string) str_replace( $source_url, get_site_url(), $post_array['guid'] );

			$file_path_exploded = explode( '/uploads', $media['guid'] );
			$file_path          = $upload_base_dir . $file_path_exploded[1];
			$filename           = basename( $file_path );
			$wp_filetype        = wp_check_filetype( $filename, null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent'    => $media['post_parent'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);


			$args        = array(
				'receiver_site_id' => (int) get_option( 'data_sync_receiver_site_id' ),
				'source_post_id'   => $post->ID,
			);
			$synced_post = SyncedPost::get_where( $args );

			if ( count( $synced_post ) ) {
//				if ( true !== get_option( 'overwrite_receiver_post_on_conflict' ) ) {
//					$post->diverged = SyncedPosts::check_date_modified( $post, $synced_posts );
// UPDATE SYNCED POSTS DATABASE TABLE BUT ONLY CHANGE DIVERGED VALUE. DO NOT CHANGE THE DATE MODIFIED!
//				$args  = array(
//					'id'       => $synced_post[0]->id,
//					'diverged' => true,
//				);
//				$where = [ 'id' => $synced_post[0]->id ];
//				$db    = new DB( SyncedPost::$table_name );
//				$db->update( $args, $where );
//					return false;
//				}
				$post->diverged = false;
				$attachment_id = $synced_post[0]->id;
			} else {
				$attachment_id = wp_insert_attachment( $attachment, $file_path, $media['post_parent'] );
			}


			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				SyncedPosts::save_to_receiver( $attachment_id, $post );
			}
		}
	}


	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/media/update',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
				),
			)
		);
	}

}