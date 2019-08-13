<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;

class File {

	/**
	 * Transfer Files Server to Server using PHP Copy
	 *
	 */
	public static function copy( string $source_base_url, string $remote_file_url, $file_contents = false ) {

		/* New file name and path for this file */
		$local_file = str_replace( $source_base_url, ABSPATH, $remote_file_url );

		/* Copy the file from source url to server */
		$copied = copy( $remote_file_url, $local_file );

		/* Add notice for success/failure */
		if ( ! $copied ) {
			$log = new Logs( 'Failed to copy ' . $remote_file_url . '.', true );
			unset( $log );
			return false;
		}

		$ext = pathinfo( $local_file, PATHINFO_EXTENSION );

		if ( ( 'php' === $ext ) && ( $file_contents ) ) {
			file_put_contents( $local_file, $file_contents );
		}

		return true;

	}

}