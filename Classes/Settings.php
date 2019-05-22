<?php namespace DataSync;

use DataSync\Error as Error;

class Settings {

  /**
   * Table prefix to save custom settings
   *
   * @var string
   */
  protected static $table_prefix = 'data_sync_';
	/**
	 * Option key to save settings
	 *
	 * @var string
	 */
	protected static $option_key = '';
	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected static $defaults = array();

	/**
	 * Get saved settings
	 * @param array $settings
	 * @return array
	 */
	public static function get( $settings ){

		if ( is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				$saved = get_option( $key, array() );
			}
		} else {
			$saved = get_option( $settings, array() );
		}

		return wp_parse_args( $saved );
	}

	public static function get_connected_sites() {

	}

	/**
	 * Save settings
	 *
	 *
	 * @param array $settings
	 */
	public static function save_options( array $settings ) {
		foreach ( $settings as $key => $value ) {
			$success = update_option( $key, $value );

			if ( $success ) {
				return true;
			} else {
				return false;
			}
		}
	}

}