<?php


namespace DataSync\Controllers;

use DataSync\Controllers\File;
use DataSync\Models\DB;
use DataSync\Models\SyncedPost;
use WP_REST_Server;
use DataSync\Models\ConnectedSite;

/**
 * Class Media
 * @package DataSync\Controllers
 */
class Media {

	/**
	 * Media constructor.
	 *
	 * @param null $all_posts
	 */
	public function __construct( $all_posts = null ) {

		if ( null === $all_posts ) {
			add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		} else {

			$connected_sites = (array) ConnectedSite::get_all();
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
								$image->featured         = false;
								$image->type             = 'image';
							}
						}
						$this->send_to_receiver( $image, $connected_sites );
					}

					$featured_image           = $post->media->featured_image;
					foreach ( $synced_posts as $synced_post ) {
						if ( (int) $featured_image->post_parent === (int) $synced_post->source_post_id ) {
							$featured_image->receiver_post_id = $synced_post->receiver_post_id;
						}
					}
					if ( ! empty( $featured_image ) ) {
						$this->send_to_receiver( $featured_image, $connected_sites );
					}

					$audio_attachments = (array) $post->media->audio;
					foreach ( $audio_attachments as $key => $audio ) {
						foreach ( $synced_posts as $synced_post ) {
							if ( (int) $audio->post_parent === (int) $synced_post->source_post_id ) {
								$audio->receiver_post_id = $synced_post->receiver_post_id;
								$audio->featured         = false;
								$audio->type             = 'audio';
							}
						}
						$this->send_to_receiver( $audio, $connected_sites );
					}

					$video_attachments = (array) $post->media->video;
					foreach ( $video_attachments as $key => $video ) {
						foreach ( $synced_posts as $synced_post ) {
							if ( (int) $video->post_parent === (int) $synced_post->source_post_id ) {
								$video->receiver_post_id = $synced_post->receiver_post_id;
								$video->featured         = false;
								$video->type             = 'video';
							}
						}
						$this->send_to_receiver( $video, $connected_sites );
					}
				}
			}

		}

	}

	/**
	 * @param $media
	 * @param $connected_sites
	 */
	public function send_to_receiver( $media, $connected_sites ) {

		$path                            = wp_parse_url( $media->guid ); // ['host'], ['scheme'], and ['path'].
		$data                            = new \stdClass();
		$data->media                     = $media;
		$data->receiver_parent_post_type = get_post_type( (int) $media->post_parent );
		$data->filename                  = basename( $path['path'] );

		foreach ( $connected_sites as $site ) {

			$excluded = $this->check_parent_isnt_excluded( $data, $site );

			if ( ! $excluded ) {
				$data->receiver_site_id = (int) $site->id;
				$auth                   = new Auth();
				$json                   = $auth->prepare( $data, $site->secret_key );
				$url                    = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/media/update';
				$response               = wp_remote_post( $url, [
					'body'        => $json,
					'httpversion' => '1.0',
					'sslverify'   => false,
					'timeout'     => 10,
					'blocking'    => true,
				] );

				if ( is_wp_error( $response ) ) {
					$log = new Logs( 'Error in Media->update() received from ' . $site->url . '. ' . $response->get_error_message(), true );
					unset( $log );

					return $response;
				}
			}

		}

	}


	public function check_parent_isnt_excluded( $data, $site ) {
		$parent_post_meta = get_post_meta( $data->media->post_parent );
		$excluded_sites   = unserialize( $parent_post_meta['_excluded_sites'][0] );

		if ( in_array( (int) $site->id, $excluded_sites ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public function update() {
		$source_data      = (object) json_decode( file_get_contents( 'php://input' ) );
		$receiver_options = (object) Options::receiver();

		// CHECK IF PARENT POST TYPE MATCHES ENABLED POST TYPES ON RECEIVER.
		if ( in_array( $source_data->receiver_parent_post_type, $receiver_options->enabled_post_types ) ) {
			$this->insert_into_wp( $source_data );
		}

		wp_send_json_success( $source_data );
	}


	/**
	 */
	public function insert_into_wp( object $source_data ) {

		$upload_dir = wp_get_upload_dir();
		$subfolder  = explode( 'wp-content/uploads', dirname( $source_data->media->guid ) )[1];
		$file_path  = $upload_dir['basedir'] . $subfolder . '/' . $source_data->filename;
		$file_url   = $upload_dir['baseurl'] . $subfolder . '/' . $source_data->filename;

		$result = File::copy( $source_data );

		if ( $result ) {

			$wp_filetype = wp_check_filetype( $source_data->filename, null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent'    => (int) $source_data->media->receiver_post_id,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $source_data->filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => (string) $file_url,
			);

			$args        = array(
				'receiver_site_id' => (int) get_option( 'data_sync_receiver_site_id' ),
				'source_post_id'   => (int) $source_data->media->ID,
			);
			$synced_post = SyncedPost::get_where( $args );

			// SET DIVERGED TO FALSE TO OVERWRITE EVERY TIME.
			$source_data->media->diverged = false;

			if ( count( $synced_post ) ) {
				$source_data->media->diverged = false;
				$attachment_id                = $synced_post[0]->receiver_post_id;
			} else {
				$attachment_id = wp_insert_attachment( $attachment, $file_path, (int) $source_data->media->receiver_post_id );
			}

			if ( ! is_wp_error( $attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				if ( ( 'audio' !== $source_data->media->type ) && ( 'video' !== $source_data->media->type ) ) {
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
					$updated_meta    = wp_update_attachment_metadata( $attachment_id, $attachment_data );
				}
				$source_data->media->diverged = false;
				SyncedPosts::save_to_receiver( $attachment_id, $source_data->media );
				if ( $source_data->media->featured ) {
					$this->update_thumbnail_id( $source_data->media, (int) $attachment_id );
				}
			} else {
				$log = new Logs( 'Post not uploaded and attached to ' . $source_data->media->post_title, true );
				unset( $log );

				return $attachment_id;
			}


		}
	}


	/**
	 * This makes sure the parent's thumbnail id to the attached image (featured image) is updated.
	 */
	private function update_thumbnail_id( $post, $attachment_id ) {
		$args               = array(
			'receiver_site_id' => (int) get_option( 'data_sync_receiver_site_id' ),
			'source_post_id'   => $post->post_parent,
		);
		$synced_post_parent = SyncedPost::get_where( $args );
		if ( $synced_post_parent ) {
			$updated = update_post_meta( (int) $synced_post_parent[0]->receiver_post_id, '_thumbnail_id', $attachment_id );
		} else {
			$log = new Logs( 'Post thumbnail not updated for ' . $post->post_title, true );
			unset( $log );
		}


	}


	/**
	 *
	 */
	public
	function register_routes() {
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