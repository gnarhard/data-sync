<?php


namespace WPDataSync;


class Settings {

	/**
	 * Option key to save settings
	 *
	 * @var string
	 */
	protected static $option_key = '_wpds_settings';
	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected static $defaults = array();
	/**
	 * Get saved settings
	 *
	 * @return array
	 */
	public static function get(){
		$saved = get_option( self::$option_key, array() );
		if( ! is_array( $saved ) || ! empty( $saved )){
			return self::$defaults;
		}
		return wp_parse_args( $saved, self::$defaults );
	}
	/**
	 * Save settings
	 *
	 * Array keys must be whitelisted (IE must be keys of self::$defaults
	 *
	 * @param array $settings
	 */
	public static function save( array  $settings ){
		//remove any non-allowed indexes before save
		foreach ( $settings as $i => $setting ){
			if( ! array_key_exists( $setting, self::$defaults ) ){
				unset( $settings[ $i ] );
			}
		}
		update_option( self::$option_key, $settings );
	}

}