<?php
/* Plugin Name: ChanceLight Data API Receiver
Plugin URI: http://nashvillegeek.com
Description: A plugin for ChanceLight properties to receive the Data API
Version: 0.02 beta
Author: Kenneth White

This plugin is not to be distributed or modifed without permission from Nashville
Geek and/or Chancelight Behavioral Health and Education

*/
// TODO - How to output locationHours schema
// TODO - Create shortcodes for maps and archive pages
// TODO - Create plan for state archive pages
// TODO - Plan shortcodes
// TODO - Create plan for 404 redirect of deleted locations



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


spl_autoload_register( 'cl_data_api_receiver_autoloader' );
function cl_data_api_receiver_autoloader( $class_name )
{
	if ( false !== strpos( $class_name, 'CLDataAPI' ) && !class_exists($class_name) )
	{
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
		$class_file = str_replace( '_', DIRECTORY_SEPARATOR, $class_name ) . '.php';
		require_once $classes_dir . $class_file;
	}
}

class CLDataAPI
{
	static $instance = false;
	static $slug = 'cl_data_api';
	static $remote_url = 'https://dataapi.wpengine.com';

	static function init()
	{
		// get instance
		self::$instance = self::get_instance();



		// build settings
		CLDataAPI::get_instance()->settings = (object) array();
		CLDataAPI::get_instance()->settings->path = dirname( __FILE__ );
		CLDataAPI::get_instance()->settings->file = basename( __FILE__, '.php' );

		if ( ! function_exists( 'get_plugin_data' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		CLDataAPI::get_instance()->settings->plugin_data = get_plugin_data( __FILE__ );
		CLDataAPI::get_instance()->settings->basename = strtolower( __CLASS__ );
		CLDataAPI::get_instance()->settings->plugin_basename = plugin_basename( __FILE__ );
		CLDataAPI::get_instance()->settings->uri = plugin_dir_url( __FILE__ );
		CLDataAPI::get_instance()->settings->pretty_name = __( 'ChanceLight Data API Receiver', CLDataAPI::get_instance()->settings->file );
		CLDataAPI::get_instance()->settings->admin_notice = '';



		// require at least PHP 5.3
		if ( version_compare( PHP_VERSION, '5.3', '<' ) )
		{
			CLDataAPI::get_instance()->settings->admin_notice = __('The %s plugin requires at least PHP 5.3. You have %s. Please upgrade and then re-install the plugin.', 'CLDataAPI');
			add_action( 'admin_notices', array( __CLASS__, 'notify_php_version' ) );
			return;
		}



		CLDataAPI_Admin::init();
		CLDataAPI_Ajax::init();
 		CLDataAPI_Cron::init();

		register_activation_hook( __FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'on_deactivation' ) );

		do_action( 'cl_data_api_loaded' );
	}



	public static function on_activation( $network_wide )
	{
		if ( function_exists( 'is_multisite' ) && is_multisite() )
		{
			if ( $network_wide  )
			{
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id )
				{
					switch_to_blog( $blog_id );
					self::single_activate();
				}
				restore_current_blog();
			}
			else
			{
				self::single_activate();
			}
		}
		else
		{
			self::single_activate();
		}

	}

	static function single_activate()
	{
    	wp_schedule_event( time(), 'every_5_minutes', 'cl_data_api_update_action' );
	}



	static function on_deactivation()
	{
    	wp_clear_scheduled_hook( 'cl_data_api_update_action' );
	}

	/**
	 * friendly notice about php version requirement
	 */
	static function notify_php_version()
	{
		if ( ! is_admin() ) return;
		?>
			<div class="error below-h2">
				<p>
				<?php
				echo sprintf(
					CLDataAPI::get_instance()->settings->admin_notice,
					CLDataAPI::get_instance()->settings->pretty_name,
					PHP_VERSION
				);
				?>
				</p>
			</div>
	<?php
	}

	/**
	 * get the instance of this class
	 * @return object the instance
	 */
	public static function get_instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * construct that can't be overwritten
	 */
	private function __construct() { }



} // CLDataAPI

// instantiate the plugin
CLDataAPI::init();
