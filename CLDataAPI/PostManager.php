<?php
/**
 * Manges creation, update and deletion of API data
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/postmanager
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_PostManager
{
	public function create_update_post() {

		// DEV: This method is WAY TOO BIG and should be dried out

		$post_modified_date = CLDataAPI_Remote::get_modified_date();
		$post_data =  json_decode( wp_remote_retrieve_body( CLDataAPI_Remote::get_remote_posts( $post_modified_date ) ) );

		// Run loop on received post data and structure data
		foreach ( $post_data as $single_location ) {
			$my_term_ids = [];
			// Run term check for regions
			$term_list_unfiltered = $single_location->_embedded->{'wp:term'};
			//print_r($term_list_unfiltered);
			foreach ($term_list_unfiltered as $single_term) {
				if ($single_term[0]->taxonomy == 'cl_location_region') {
					// This part of the terms array has Region objects
					foreach ($single_term as $single_obj) {
						$term_response = CLDataAPI_TermManager::insert_cl_location_region( $single_obj->name );
						if (!empty($term_response) ) {
							$my_term_ids[] = $term_response['term_id'];
						// Check to see if region exists in the database
						} elseif($term_check = term_exists($single_obj->name, 'cl_location_region'))  {
							if (!empty($term_check)) $my_term_ids[] = intval($term_check['term_id']);
						} else {
							// Do nothing
						};
						//print_r('Term Response ');
						//print_r($term_response);
						//print_r('Term Check ');
						//print_r($term_check);
						//print_r('Term IDs ');
						//print_r($my_term_ids);
					}
				}
			}

			$my_post = []; // Initialize variable
			$my_post = array(
				'post_title'    => $single_location->title->rendered,
				'post_content'  => $single_location->content->rendered,
				'post_name'		=> $single_location->slug,
				'post_status'   => 'publish',
				'post_author'   => 1,
				'post_type'	  	=> 'cl_location',
				'post_modified_gmt' => $single_location->modified_gmt,
				'meta_input'	=> array(
					'location_id'	=> $single_location->id,
					'post_modified_microtime' => $single_location->post_modified_microtime,
					// Keep this here in case we need to preserve all ACF data as post_meta on post creation
					//'acf'			=> $single_location->acf
				)
			);
			//print_r($my_post);

			// if post data is formatted correctly, do this stuff
			if ( !empty( $my_post['post_title'] ) && !empty( $my_post['meta_input']['location_id'] ) ) {

				// Set new global baseline date
				if ( false != $my_post['meta_input']['post_modified_microtime'] ) {
					CLDataAPI_Remote::set_modified_date( $my_post['meta_input']['post_modified_microtime'] );
				}

				// Run query to check if Location exists based on transmitter ID
				// Yes, update existing Location.
				// No, create new Location.
				$new_query = new WP_Query(array(
					'posts_per_page' 	=> 1,
					'post_type' 		=> 'cl_location',
					'meta_key' 			=> 'location_id',
					'meta_value' 		=> $single_location->id,

				) );
				if ( $new_query->have_posts() ) :
					while ( $new_query->have_posts() ) : $new_query->the_post();
						$post_id = wp_update_post( $my_post, true );
						if (is_wp_error($post_id)) {
							$errors = $post_id->get_error_messages();
							foreach ($errors as $error) {
								error_log( $error );
							}
						} else {
							self::update_acf( $post_id, $single_location->acf );
							// Set location region
							if (!empty($my_term_ids)) {
								wp_set_object_terms( $post_id, $my_term_ids, 'cl_location_region' );
							}
							// Set location state
							if (!empty( get_post_meta( $post_id , 'state' , true ) ) ) {
								wp_set_object_terms( $post_id, get_post_meta( $post_id , 'state' , true ), 'cl_location_state' );
							}
							// Post modified is done automatically and has to be edited in the DB after post creation
							// Reset post modified date to date from API
							global $wpdb;
							$query = "UPDATE $wpdb->posts
							          SET
							              post_modified = '$single_location->modified',
							              post_modified_gmt = '$single_location->modified_gmt'
							          WHERE
							              ID = '$post_id'";
							$wpdb->query($query);
						}
					endwhile;
				else :
					$post_id = wp_insert_post( $my_post, true );
					if (is_wp_error($post_id)) {
						$errors = $post_id->get_error_messages();
						foreach ($errors as $error) {
							error_log( $error );
						}
					} else {
						self::update_acf( $post_id, $single_location->acf );
						if (!empty($my_term_ids)) {
							wp_set_object_terms( $post_id, $my_term_ids, 'cl_location_region' );
						}
						// Set location state
						if (!empty( get_post_meta( $post_id , 'state' , true ) ) ) {
							wp_set_object_terms( $post_id, get_post_meta( $post_id , 'state' , true ), 'cl_location_state' );
						}
						// Post modified is done automatically and has to be edited in the DB after post creation
						// Reset post modified date to date from API
						global $wpdb;
						$query = "UPDATE $wpdb->posts
						          SET
						              post_modified = '$single_location->modified',
						              post_modified_gmt = '$single_location->modified_gmt'
						          WHERE
						              ID = '$post_id'";
						$wpdb->query($query);
					}
				endif;
				wp_reset_postdata();
			}

			// Insert the post into the database
			//$post_id = wp_insert_post( $my_post, true );
			//print_r($post_id);
			//echo 'Post ID ' . $post_id;
		}
	}

	/**
	 * update_acf function
	 * This field relies on the successful post save to return a post id
	 *
	 * @access public
	 * @param integer $post_id
	 * @param object $my_post_acf
	 * @return void
	 */
	public function update_acf( $post_id, $my_post_acf ) {
		$my_post_acf = CLDataAPI_Helper::objectToArray( $my_post_acf );

		// ACF requires image attachment ID as value for image fields
		// We need to search the entire array for images, sideload them,
		// and replace their values with attachment ID's

		// FIELD ID'S ARE REQUIRED FOR IMAGES TO ATTACH CORRECTLY VIA REPEATER
		$my_post_acf = CLDataAPI_Helper::ACFImageArrayToID( $post_id, $my_post_acf );

		//print_r( $my_post_acf );

		foreach ( $my_post_acf as $field_key => $value ) {
			if ($field_key == 'staff') {
				$field_key = 'field_57cf31a4c56e0';
			}
			if ($field_key == 'address_1') {
				$field_key = 'field_57cf2a3b66f79';
			}
			if ($field_key == 'location_photo_1') {
				$field_key = 'field_57d04ef9678ee';
			}
			if ($field_key == 'location_photo_2') {
				$field_key = 'field_57d04f12678ef';
			}
			if ($field_key == 'location_photo_3') {
				$field_key = 'field_57d04f13678f0';
			}
			update_field( $field_key, $value, $post_id );
		}
		// address_1
		// address_2
		// city
		// state
		// zip
		// phone_1
		// phone_2
		// fax
		// email
		// website_address
		// hours_su
		// hours_mo
		// hours_tu
		// hours_we
		// hours_th
		// hours_fr
		// hours_sa
		// staff
			// staff_name
			// staff_title
			// staff_email
			// staff_phone
			// staff_bio
			// staff_photo
		// location_photo_1
		// location_photo_2
		// location_photo_3
		// latitude
		// longitude
	}
}