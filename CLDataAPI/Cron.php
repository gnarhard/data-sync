<?php
/**
 * Control connections to the remote
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/remote
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */

/**
 * Helpful debugging functions
 *
 * echo '<pre>'; print_r(_get_cron_array()); echo '</pre>';
 * echo '<pre>'; print_r(wp_get_schedules()); echo '</pre>';
 * echo '<pre>'; print_r(wp_next_scheduled( 'cl_data_api_update')); echo '</pre>';
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Cron
{
	/**
	 * Initialize actions and filter functions
	 *
	 * @access public
	 * @static
	 */
	public static function init()
	{
		add_filter( 'cron_schedules', array( __CLASS__, 'ngeek_custom_cron_schedule' ) );
		add_action( 'cl_data_api_update_action', array( __CLASS__, 'cl_data_api_update' ), 9 );
	}

	/**
	 * Adds a custom cron schedule for every 5 minutes.
	 *
	 * @param array $schedules An array of non-default cron schedules.
	 * @return array Filtered array of non-default cron schedules.
	 */
	public function ngeek_custom_cron_schedule( $schedules ) {
	    $schedules[ 'every_5_minutes' ] = array( 'interval' => 5 * MINUTE_IN_SECONDS, 'display' => __( 'Every 5 minutes', 'ngeek' ) );
	    return $schedules;
	}

	/**
	 * Get remote posts when schedule is triggered
	 *
	 * @access public
	 * @return void
	 */
	public function cl_data_api_update() {
		CLDataAPI_Helper::ngeek_delete_locations();
		CLDataAPI_PostManager::create_update_post();
	}
}





