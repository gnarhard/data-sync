<?php
/**
 * AJAX scripts
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/ajax
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Ajax
{
	public static function init()
	{
		add_action( 'wp_ajax_reset_modified_date', array( __CLASS__, 'ngeek_reset_modified_date_ajax' ) );
		add_action( 'wp_ajax_import_some_locations', array( __CLASS__, 'ngeek_import_some_locations_ajax' ) );
	}

	/**
	 * ngeek_reset_modified_date_php function.
	 *
	 * @access public
	 * @return string $reset_modified_date
	 */
	public function ngeek_reset_modified_date_ajax() {
		update_option('cllocation_modified_date','0');

		echo 'now reset';

		wp_die();
	}

	public function ngeek_import_some_locations_ajax() {
		// Delete action added for immediate gratification
		CLDataAPI_Helper::ngeek_delete_locations();
		CLDataAPI_PostManager::create_update_post();
		$new_modified_date = CLDataAPI_Remote::get_modified_date();
		echo $new_modified_date;

		wp_die();
	}

}