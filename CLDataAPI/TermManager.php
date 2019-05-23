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

class CLDataAPI_TermManager
{
	/**
	 * insert_cl_location_region function
	 *
	 * @access public
	 * @param string $term_name
	 * @return int|false $term_id
	 */
	public function insert_cl_location_region( $term_name ) {
		$term_id = wp_insert_term( $term_name, 'cl_location_region');
		if (!is_wp_error($term_id)) {
			return $term_id;
		} else {
			return false;
		}
	}

}