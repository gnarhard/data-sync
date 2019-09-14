<?php

namespace DataSync;


use DataSync\Controllers\Logs;
use WP_Error;

/**
 * Class Helpers
 * @package DataSync
 */
class Helpers {

	/**
	 * @param $url
	 *
	 * Format URL to make sure https is used
	 *
	 * @return string|string[]|WP_Error|null
	 */
	public static function format_url( $url ) {

		$parsed_url = wp_parse_url( $url );
		if ( ! isset( $parsed_url['scheme'] ) ) {
			$url = 'https://' . $url;
		}

		$url = preg_replace( "/^http:/i", "https:", $url );

		$exploded_url = explode( '.', $url );

		if ( ! isset( $exploded_url[1] ) ) {
			$log = new Logs( 'ERROR: Connected site url could not be processed.', true );
			unset( $log );

			return new WP_Error( 'database_error', 'DB Logs: Connected site was not saved.', array( 'status' => 501 ) );
		}

		return $url;
	}

	/**
	 * @param $obj
	 *
	 * Recursively convert and object to an array
	 *
	 * @return array
	 */
	public static function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = self::object_to_array( $val );
			}
		} else {
			$new = $obj;
		}

		return $new;
	}


	public static function delete_media( $dir ) {
		if ( is_dir( $dir ) ) {
			$files = scandir( $dir );
			foreach ( $files as $file ) {
				if ( $file !== "." && $file !== ".." ) {
					self::delete_media( "$dir/$file" );
				}
			}
			rmdir( $dir );
		} else if ( file_exists( $dir ) ) {
			unlink( $dir );
		}
	}
}