<?php namespace DataSync;


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
	public static function get($settings){

    foreach ( $settings as $key => $value){
      $saved = get_option( $key, array() );
//      if( ! is_array( $saved ) || ! empty( $saved )){
//        return self::$defaults;
//      }
    }

		return wp_parse_args( $saved );
	}

	/**
	 * Save settings
	 *
	 *
	 * @param array $settings
	 */
	public static function save( array  $settings ){
		//remove any non-allowed indexes before save
		foreach ( $settings as $key => $value){
      update_option( $key, $value );
		}
	}

}