<?php


namespace DataSync\Controllers;

use DataSync\Models\SyncedPost;
use DataSync\Routes\SyncedPostsRoutes;
use DataSync\Tools\Helpers;
use WP_REST_Request;
use WP_REST_Response;
use stdClass;
use DataSync\Models\DB;
use DataSync\Models\ConnectedSite;

class SyncedPosts {

	public function __construct() {
		new SyncedPostsRoutes( $this );
		add_action( 'delete_post', array( $this, 'delete' ), 10 );
	}

	public static function filter( object $post, $source_data, $synced_posts ) {
		// THIS WILL RETURN AN ARRAY WITH ONE VALUE OF 0 IF NOTHING IS EXCLUDED.
		$excluded_sites = unserialize( $post->post_meta->_excluded_sites[0] );

		if ( in_array( (int) get_option( 'data_sync_receiver_site_id' ), $excluded_sites ) ) {
			return false;
		} else {

			$published_before_sync_date = ConnectedSites::check_sync_date( $post, $source_data );

			if ( ( ! $published_before_sync_date ) && ( true !== $source_data->options->overwrite_receiver_post_on_conflict ) ) {
				return false;
			}

			$post->diverged = false;
			$post->synced   = (bool) self::is_synced( $post, $synced_posts );

			if ( $post->synced ) {
				$post->diverged = self::check_date_modified( $post, $synced_posts );

				if ( $post->diverged ) {
					if ( true !== $source_data->options->overwrite_receiver_post_on_conflict ) {
						$logs = new Logs();
						$logs->set( 'Post ' . $post->post_title . ' was updated more recently on receiver.', true );

						$synced_post = SyncedPost::get_where( array(
								'source_post_id'   => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ),
								'receiver_site_id' => (int) get_option( 'data_sync_receiver_site_id' ),
							) );

						// UPDATE SYNCED POSTS DATABASE TABLE BUT ONLY CHANGE DIVERGED VALUE. DO NOT CHANGE THE DATE MODIFIED!
						$args  = array(
							'id'       => (int) $synced_post[0]->id,
							'diverged' => 1,
						);
						$where = array( 'id' => (int) $synced_post[0]->id );
						$db    = new DB( SyncedPost::$table_name );
						$db->update( $args, $where );

						return false;
					}
				}
			}

			return $post;
		}
	}

	public static function check_date_modified( object $post, array $synced_posts ) {
		foreach ( $synced_posts as $synced_post ) {
			if ( ( (int) $post->ID === (int) $synced_post->source_post_id ) && ( (int) get_option( 'data_sync_receiver_site_id' ) === (int) $synced_post->receiver_site_id ) ) {
				$synced_modified_timestamp   = strtotime( $synced_post->date_modified );
				$receiver_modified_timestamp = get_post_modified_time( 'U', true, $synced_post->receiver_post_id,
					false );

				// IF RECEIVER POST WAS MODIFIED LATER THAN THE SYNCED POST WAS.
				if ( $receiver_modified_timestamp > $synced_modified_timestamp ) {
					return true;
				} else {
					return false;
				}
			}
		}

		return null;
	}


	public static function is_synced( object $post, array $synced_posts ) {
		if ( self::get_receiver_post_id( $post, $synced_posts ) ) {
			return true;
		}

		return false;
	}

//	public static function retrieve_from_receiver( $data_sync_start_time ) {
//		$connected_sites = (array) ConnectedSite::get_all();
//		$all_data        = array();
//
//		foreach ( $connected_sites as $site ) {
//			$url      = Helpers::format_url( trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/all' );
//			$args     = array(
//				'body' => array( 'datetime' => $data_sync_start_time ),
//			);
//			$response = wp_remote_get( $url, $args );
//
//			if ( is_wp_error( $response ) ) {
//				$logs = new Logs();
//				$logs->set( 'Error in SyncedPosts::retrieve_from_receiver received from ' . get_site_url() . '. ' . $response->get_error_message(), true );
//				return $response;
//			}
//
//			$all_data[] = json_decode( wp_remote_retrieve_body( $response ) );
//		}
//
//		return $all_data;
//	}

	public static function get_receiver_post_id( $post, $synced_posts ) {
		foreach ( $synced_posts as $synced_post ) {
			// RECEIVER SITE ID AND SOURCE POST ID MUST MATCH TO BE A SUCCESSFUL SYNCED POST.
			if ( ( (int) $post->ID === (int) $synced_post->source_post_id ) && ( (int) get_option( 'data_sync_receiver_site_id' ) === (int) $synced_post->receiver_site_id ) ) {
				return $synced_post->receiver_post_id;
			}
		}

		return false;
	}


//	public static function get_synced_post_data( object $post ) {
//		$url = trailingslashit( $post->source_url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/synced_posts/' . get_option( 'data_sync_receiver_site_id' ) . '/' . $post->ID;
//		$url = Helpers::format_url( $url );
//
//		$response = wp_remote_get( $url );
//
//		if ( is_wp_error( $response ) ) {
//			$logs = new Logs();
//			$logs->set( 'SyncedPosts: ' . $response->get_error_message(), true );
//
//			return false;
//		}
//
//		return json_decode( wp_remote_retrieve_body( $response ) )->data;
//	}


	public function get( WP_REST_Request $request ) {
		$data = (object) $request->get_url_params();

		$result = SyncedPost::get_where( array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			) );

		wp_send_json_success( $result );
	}

	public function get_all() {
		$response = new WP_REST_Response( SyncedPost::get_all() );
		$response->set_status( 201 );

		if ( is_wp_error( $response ) ) {
			$logs = new Logs();
			$logs->set( 'Failed to get synced posts.', true );
		}

		return $response;
	}

	public function get_after_date( WP_REST_Request $request ) {
		if ( $request ) {
			$datetime = $request->get_param( 'datetime' );
			if ( $datetime ) {
				return SyncedPost::get_all_and_sort( array( 'datetime' => 'DESC' ), $datetime );
			}
		} else {
			return array();
		}
	}

	public static function save_to_receiver( int $receiver_post_id, object $source_post ) {
		$data                   = new stdClass();
		$data->source_post_id   = $source_post->ID;
		$data->receiver_post_id = $receiver_post_id;
		$data->receiver_site_id = get_option( 'data_sync_receiver_site_id' );
		$data->name             = $source_post->post_title;
		$data->post_type        = $source_post->post_type;
		$data->diverged         = $source_post->diverged;
		$data->date_modified    = current_time( 'mysql', 1 );

		return self::save( $data );
	}

	public static function save( $data ) {
		$existing_synced_post = SyncedPost::get_where( array(
				'source_post_id'   => (int) filter_var( $data->source_post_id, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $data->receiver_site_id, FILTER_SANITIZE_NUMBER_INT ),
			) );

		if ( count( $existing_synced_post ) ) {
			$data->id = $existing_synced_post[0]->id;

			return SyncedPost::update( $data );
		} else {
			return SyncedPost::create( $data );
		}
	}

	public function save_to_source( WP_REST_Request $request ) {
		$receiver_synced_posts = json_decode( $request->get_body() );
		self::save_all_to_source( $receiver_synced_posts );
		$return_data          = new stdClass();
		$return_data->message = 'All receiver synced posts saved to source.';
		wp_send_json_success( $return_data );
	}

	public static function save_all_to_source( array $receiver_synced_posts ) {
		foreach ( $receiver_synced_posts as $site_synced_posts ) {
			foreach ( $site_synced_posts as $synced_post ) {
				$result = self::save( $synced_post );
			}
		}
	}

	public function delete( $post_id ) {

		// BULK DELETE
		if ( get_option( 'source_site' ) ) {
			$source_data                 = new stdClass();
			$source_data->source_post_id = $post_id;
			$connected_sites             = (array) ConnectedSite::get_all();

			$post = get_post( $post_id );

			foreach ( $connected_sites as $site ) {
				$logs = new Logs();
				$logs->set( 'STARTING BULK DELETE FOR ' . $site->url );

				$args        = array(
					'receiver_site_id' => (int) $site->id,
					'source_post_id'   => $post_id,
				);
				$synced_post = SyncedPost::get_where( $args );

				// WordPress TRIES TO DELETE ALL DATA ASSOCIATED WITH THE POST ID, INCLUDING REVISIONS.
				// WE DON'T SYNC REVISIONS SO WE CAN SKIP IF IT ISN'T IN THE SYNCED POST TABLE.
				if ( count( $synced_post ) ) {
					$synced_post = $synced_post[0];

					$source_data->receiver_post_id = $synced_post->receiver_post_id;
					$source_data->debug            = get_option( 'debug' );
					$source_data->receiver_site_id = (int) $site->id;
					$auth                          = new Auth();
					$json                          = $auth->prepare( $source_data, $site->secret_key );
					$site                          = ConnectedSites::get_api_url( $site );
					$url                           = $site->api_url . DATA_SYNC_API_BASE_URL . '/synced_posts/delete_receiver_post/';
					$response                      = wp_remote_post( $url, array(
							'body'        => $json,
							'httpversion' => '1.0',
							'sslverify'   => false,
							'timeout'     => 10,
							'blocking'    => true,
						) );

					if ( is_wp_error( $response ) ) {
						$logs = new Logs();
						$logs->set( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(),
							true );

						return $response;
					} else {
						SyncedPost::delete( $synced_post->id );
					}

					$logs = new Logs();
					$logs->set( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . $site->url );
				}
			}
		} else {
			// RECEIVER SIDE DELETION.

			$post             = get_post( $post_id );
			$receiver_site_id = (int) get_option( 'data_sync_receiver_site_id' );

			$args        = array(
				'receiver_site_id' => $receiver_site_id,
				'receiver_post_id' => $post_id,
			);
			$synced_post = SyncedPost::get_where( $args );

			if ( count( $synced_post ) ) {
				$receiver_data                   = new stdClass();
				$receiver_data->receiver_post_id = $post_id;
				$receiver_data->debug            = get_option( 'debug' );
				$receiver_data->receiver_site_id = $receiver_site_id;
				$auth                            = new Auth();
				$json                            = $auth->prepare( $receiver_data, get_option( 'secret_key' ) );
				$url                             = trailingslashit( get_option( 'data_sync_source_site_api_url' ) ) . DATA_SYNC_API_BASE_URL . '/synced_posts/delete_synced_post/';
				$response                        = wp_remote_post( $url, array(
						'body'        => $json,
						'httpversion' => '1.0',
						'sslverify'   => false,
						'timeout'     => 10,
						'blocking'    => true,
					) );

				if ( is_wp_error( $response ) ) {
					$logs = new Logs();
					$logs->set( 'Failed to delete post: ' . $post->post_title . '(' . $post->post_type . '). ' . $response->get_error_message(),
						true );

					return $response;
				} else {
					$deleted = SyncedPost::delete( $synced_post[0]->id );

					$logs = new Logs();
					$logs->set( 'Finished deleting post: ' . $post->post_title . '(' . $post->post_type . ') on ' . get_site_url() );
				}
			}
		}
	}

	public function delete_post() {
		$data = (object) json_decode( file_get_contents( 'php://input' ) );
		$logs = new Logs();
		$logs->set( 'Received delete request for post: ' . wp_json_encode( $data ) );

		// MUST TRASH BEFORE DELETION TO PERMANENTLY DELETE.
		wp_trash_post( $data->receiver_post_id );
		wp_delete_post( $data->receiver_post_id );

		global $wpdb;
		$delete_orphaned_term_relationships_query = 'DELETE FROM ' . $wpdb->prefix . 'term_relationships WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM posts)';
		$db                                       = new DB( 'term_relationships' );

		return $this->delete_synced_post( null, $data );
	}

	public function delete_synced_post( WP_REST_Request $data_from_receiver, $data_from_source = false ) {
		if ( ! $data_from_source ) {
			$source_data = (object) json_decode( file_get_contents( 'php://input' ) );
			$logs        = new Logs();
			$logs->set( 'Received delete request for post: ' . wp_json_encode( $source_data ) );
			$data = $source_data;
		} else {
			$data = $data_from_receiver;
		}

		$args        = array(
			'receiver_site_id' => (int) $data->receiver_site_id,
			'receiver_post_id' => $data->receiver_post_id,
		);
		$synced_post = SyncedPost::get_where( $args );

		if ( count( $synced_post ) ) {
			return SyncedPost::delete( $synced_post[0]->id );
		}
	}

	public function display_post( WP_REST_Request $request ) {
		$request_body = json_decode( $request->get_body() );
		$post         = $request_body->post_to_get;
		include_once DATA_SYNC_PATH . 'public/views/admin/options/synced-posts-table.php';
		\DataSync\display_post( $post, $request_body->source_data->connected_sites,
			(array) $request_body->receiver_data );
	}
}
