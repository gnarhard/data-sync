<?php
/**
 * Control connections to the remote
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/remote
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Remote
{
	// the instance of this object
	//private static $instance;

	/**
	 * get_remote_posts function.
	 *
	 * @access public
	 * @param bool|string $modified_date (default: false)
	 * @param int $per_page (default: 100)
	 * @return mixed $response
	 */
	public function get_remote_posts( $modified_date = false, $per_page = 5 ) {

		// SITE MUST HAVE A CONNECTION ID BEFORE IT WILL CONNECT TO REMOTE

		if ( $cl_location_id = get_option( 'cl_location_id' ) ) {

			// Sort locations by oldest to newest
			// ?filter[meta_key]=post_modified_microtime&filter[meta_value]=1487196080.4555&filter[meta_compare]=%3C
			// ?filter[date_query][after]=2016-08-30T23:01:30&filter[date_query][column]=post_modified_gmt
			$date_to_check = self::get_modified_date();
			$api_query_arr = [];
				$api_query_arr[] = 'cl_location_site=' . $cl_location_id;
			$api_query_arr[] = 'filter[meta_key]=post_modified_microtime';
			if ( false !== $date_to_check ) {
				$api_query_arr[] = 'filter[meta_value]=' . $date_to_check;
				$api_query_arr[] = 'filter[meta_compare]=%3E'; // greater-than
			}
			$api_query_arr[] = 'filter[orderby]=meta_value_num';
			$api_query_arr[] = 'per_page=' . $per_page;
			$api_query_arr[] = 'order=asc';
			// Add _embedded keyword to get taxonomy term data
			$api_query_arr[] = '_embed';
			if ( !empty( $api_query_arr ) ) {
				$api_query_string = '?' . implode('&', $api_query_arr );
			}
			$response = wp_remote_get( CLDataAPI::$remote_url . '/wp-json/wp/v2/locations-api/' . $api_query_string);
			if( is_wp_error( $response ) ) {
				return array();
			}

			return $response;
		} else {
			error_log('Your site is missing the corresponding site ID from the remote host.');
		}

	}

	public function debug_response() {
		$response = json_decode( wp_remote_retrieve_body( self::get_remote_posts() ) );
		//print_r($response);
		foreach ( $response as $single_location ) {
			echo '<br/>' . $single_location->title->rendered;
			echo '<br/>' . $single_location->content->rendered;
		}
	}

	public function get_modified_date() {
		$modified_date = get_option( 'cllocation_modified_date' );
		return $modified_date;
	}

	/**
	 * set_modified_date function.
	 *
	 * @access public
	 * @param string $modified_date (default: false)
	 * @return void
	 */
	public function set_modified_date( $modified_date = false ) {
		update_option( 'cllocation_modified_date', $modified_date );
	}

	/**
	 * get_remote_deleted_posts function.
	 *
	 * @access public
	 * @return array $delete_ids_arr
	 */
	public function get_remote_deleted_posts() {
		$response = wp_remote_get( CLDataAPI::$remote_url . '/wp-json/cllocations/v1/delete-ids');
				if( is_wp_error( $response ) ) {
			return array();
		}
		$delete_ids = json_decode( wp_remote_retrieve_body( $response ) );
		$delete_ids_arr = explode( ',', $delete_ids );
		return $delete_ids_arr;
	}
}