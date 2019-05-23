<?php


namespace DataSync\Controllers;


/**
 * Class Error
 * @package DataSync
 */
class Error {

	/**
	 * Error constructor.
	 *
	 * Makes sure WP_Filesystem allows writing to error.log before firing log()
	 */
	public function __construct() {

		$url = wp_nonce_url( '/wp-admin/options-general.php?page=data-sync-settings', 'error_log' );
		if ( false === ( $creds = request_filesystem_credentials( $url, '', false, false, null ) ) ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, false, null );

			return false;
		}

		return true;

	}

	/**
	 * @param $error
	 *
	 * Prepends errors to ../error.log
	 */
	public function log( $error ) {

		$new_line = $this->get_timestamp() . ': ' . $error;

		$file_text = $new_line . $this->get_log();

		global $wp_filesystem;

		$wp_filesystem->put_contents(
			DATA_SYNC_PATH . 'error.log',
			$file_text,
			FS_CHMOD_FILE // predefined mode settings for WP files.
		);
	}

	/**
	 * @return mixed
	 *
	 * Gets contents from ../error.log
	 */
	public function get_log() {
		global $wp_filesystem;

		return $wp_filesystem->get_contents(
			DATA_SYNC_PATH . 'error.log'
		);
	}

	/**
	 * @return false|string
	 *
	 * Gets current timestamp
	 */
	private function get_timestamp() {
		return current_time( 'm/d/Y h:i:s' );
	}

}
