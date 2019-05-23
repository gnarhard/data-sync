<?php
/**
 * Formatting helper functions
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/helper
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Helper
{

	/**
	 * objectToArray function.
	 *
	 * @access public
	 * @param object $data
	 * @return array $data
	 */
	public function objectToArray($data) {
        if (is_object($data)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map([self, __METHOD__], $data);
        }
        else {
            // Return array
            return $data;
        }
    }

	/**
	 * ACFImageDataPrepare function.
	 *
	 * Scans an array of ACF data for image fields and prepares the data for processing
	 * Replaces image data with attachment ID on success
	 *
	 * @access public
	 * @param integer $post_id
	 * @param array $data
	 * @return array $data
	 */
    public function ACFImageArrayToID( $post_id, $data ) {
	    $new_data = array();
	    foreach ( $data as $single_item => $single_item_child) {
		    if ( is_array( $single_item_child) ) {
		    	if ( isset($single_item_child['type']) && $single_item_child['type'] == 'image' ){
			    	$image_data = array(
						'url'			=> $single_item_child['url'],
						'post_content'	=> $single_item_child['description'],
						'post_excerpt'	=> $single_item_child['caption'],
						'alt'			=> $single_item_child['alt'],
						'modified'		=> $single_item_child['modified'],
			    	);
					//print_r ( $image_data );
					$new_data[$single_item] = self::ImageDataToAttachment( $post_id, $image_data );
			    } else {
				    $new_data[$single_item] = self::ACFImageArrayToID( $post_id, $single_item_child );
				}

		    } else {
			    $new_data[$single_item] = $single_item_child;
			}
	    }
	    return $new_data;
    }

	/**
	 * ImageDataToAttachment function.
	 *
	 * Receives an array of image data, sideloads the image, and returns attachment ID
	 *
	 * @access public
	 * @param integer $post_id
	 * @param array $iamgedata
	 * @return integer $attachemnt_id
	 */
    public function ImageDataToAttachment( $post_id = 0, $image_data ) {

		if ( ! function_exists( 'download_url' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		global $wpdb;
		$url = $image_data['url'];
		$found_key = $found_id = $attach_id = $cllocation_ids = '';
		$temp_file = download_url( $url, 500 );
		if ( ! is_wp_error( $temp_file ) ) {
			$file_type = wp_check_filetype( $temp_file );

			// array based on $_FILE as seen in PHP file uploads
			$file = array(
				'name'     => basename( $url ), // ex: wp-header-logo.png
				'type'     => $file_type['type'],
				'tmp_name' => $temp_file,
				'error'    => 0,
				'size'     => filesize( $temp_file ),
			);

			$overrides = array(
				// tells WordPress to not look for the POST form
				// fields that would normally be present, default is true,
				// we downloaded the file from a remote server, so there
				// will be no form fields
				'test_form'   => false,
				// setting this to false lets WordPress allow empty files, not recommended
				'test_size'   => true,
				// A properly uploaded file will pass this test.
				// There should be no reason to override this one.
				'test_upload' => true,
			);

			// move the temporary file into the uploads directory

			// We need to check to see if the file already exists here by filename BEFORE file is uploaded to directory
			// This should be a filename and post-id pair... I need the post ID to check modified date
			$cllocation_ids = self::ngeek_get_location_attachemnt_id_and_filenames();
			// Check first available instance of image
			if ( $cllocation_ids !== null && $cllocation_ids !== false ) {
				$found_key = array_search($file['name'], array_column($cllocation_ids, 'filename'));
			}
			if ( $found_key !== null && $found_key !== false ) {
				$found_id = $cllocation_ids[$found_key]['ID'];
			}
			if ( $found_id !== null && $found_id !== false ) {
				$origin_modified_date = $wpdb->get_var("SELECT post_modified FROM {$wpdb->posts} WHERE ID = $found_id");
			}

			//print_r( 'Origin: ' . strtotime($origin_modified_date) . ' New Image Data: ' . strtotime($image_data['modified']) );
			// If found, check returned attachemnt ID with current $image_data['modified']
			if ( strtotime($origin_modified_date) == strtotime($image_data['modified'] )) {
				// Do not sideload image (maybe instead use existing image? return existing $attach ID)
				$attach_id = $found_id;
				return $attach_id;
			} else {
				//print_r( '</br>Matching Key: ' . $found_key . ' Matching ID: ' . $found_id . '</br>');
				//print_r( '</br>Incoming Date: ' . $image_data['modified'] . ' Matching ID: ' . $found_id . '</br>');
				//print_r( '</br>Origin Filename: ' . $file['name'] . ' Match Filename: ' . $cllocation_ids[$found_key]['filename'] . '</br>');

				$results = wp_handle_sideload( $file, $overrides );

				if ( ! empty( $results['error'] ) ) {

					error_log( $results['error'] );

					return false;
				} else {
					//print_r( $image_data );

					$filename  = $results['file']; // full path to the file
					$local_url = $results['url']; // URL to the file in the uploads dir
					$type      = $results['type']; // MIME type of the file

					// Prepare an array of post data for the attachment.
					$attachment_data = array(
						'guid'           => $results['file'],
						'post_mime_type' => $results['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => $image_data['post_content'],
						'post_status'    => 'inherit',
						'post_excerpt'   => $image_data['post_excerpt'],
						'post_parent'    => $post_id,
						// Post modified is done automatically and has to be edited in the DB after post creation
						//'post_modified'	 => $image_data['modified']
					);

					$attach_id = wp_insert_attachment( $attachment_data, $filename );

					// Generate the metadata for the attachment, and update the database record.
					$attachment_meta_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attachment_meta_data );
					if ( !empty( $image_data['alt'] )) {
						update_post_meta($attach_id, '_wp_attachment_image_alt', $image_data['alt']);
					}
					// Post modified is done automatically and has to be edited in the DB after post creation
					// Reset post modified date to date from API
					$query = "UPDATE $wpdb->posts
					          SET
					              post_modified = '" . $image_data['modified'] . "',
					              post_modified_gmt = '" . $image_data['modified'] . "'
					          WHERE
					              ID = '$attach_id'";
					$wpdb->query($query);
					return $attach_id;
				}

			}
		} else {

			error_log( $temp_file->get_error_message() );
			return false;
		}
	}

	/**
	 * ngeek_get_meta_values
	 *
	 * Return sorted array of meta values from a supplied key and post-type
	 *
	 * @access public
	 * @param string $key
	 * @param string $type
	 * @param string $status
	 *
	 * @return array $meta_values
	 */
    public function ngeek_get_meta_values( $key = '', $type = 'post', $status = 'publish' ) {

	    global $wpdb;

	    if( empty( $key ) )
	        return;

	    $meta_values = $wpdb->get_col( $wpdb->prepare( "
	        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
	        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	        WHERE pm.meta_key = '%s'
	        AND p.post_status = '%s'
	        AND p.post_type = '%s'
	    ", $key, $status, $type ) );

		sort( $meta_values );
	    return $meta_values;
	}

	/**
	 * ngeek_get_location_ids
	 *
	 * Return string of ids for cl_location posts
	 *
	 * @access public
	 *
	 * @return string
	 */
    public function ngeek_get_location_ids() {
		$location_id_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => 'cl_location',
			'post_status' => 'publish',
			'fields' => 'ids'
		) );
		if ( $location_id_query->have_posts() ) :
			//print_r($location_id_query->posts);
			$location_ids = implode(',', $location_id_query->posts);
		endif;
		wp_reset_postdata();
		return $location_ids;
	}

	/**
	 * ngeek_get_location_attachemnt_filenames
	 *
	 * Return array of basenames to compare
	 *
	 * @access public
	 *
	 * @return array $cl_attachment_filenames
	 */
    public function ngeek_get_location_attachemnt_id_and_filenames() {
	    $cl_attachment_filenames = [];
	    $cl_location_ids = self::ngeek_get_location_ids();
		global $wpdb;
		$attachment_values = $wpdb->get_results( $wpdb->prepare( "
	        SELECT ID,guid
	        FROM wp_posts v
	        WHERE v.post_type = 'attachment'
	        AND v.post_parent IN ( $cl_location_ids )
	    ",''));
	    foreach( $attachment_values as $i => $attachment_value ) {
		    $returned_image_values[$i]['ID'] = $attachment_value->ID;
		    $returned_image_values[$i]['filename'] = basename( $attachment_value->guid );
	    }
	    return $returned_image_values;

    }
	/**
	 * ngeek_get_location_attachemnt_filenames
	 *
	 * Return array of basenames to compare
	 *
	 * @access public
	 *
	 * @return array $cl_attachment_filenames
	 */
    public function ngeek_delete_locations() {
	    global $wpdb;
	    $delete_ids_arr = CLDataAPI_Remote::get_remote_deleted_posts();
	    // Get post that matches location_id postmeta
	    foreach ($delete_ids_arr as $delete_id_single) {
		    $delete_id = '';
		    $meta_key = 'location_id';
		    $meta_value = $delete_id_single;
			$delete_id = $wpdb->get_var( $wpdb->prepare(
				"
					SELECT post_id
					FROM $wpdb->postmeta
					WHERE meta_key = %s
					AND meta_value = %d
				",
				$meta_key, $meta_value
			) );
			if ( $delete_id > 0 && is_numeric( $delete_id )) {
				wp_delete_post( $delete_id, true );
			}
	    }
	}
}