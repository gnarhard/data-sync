<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;

class File {

	/**
	 * Transfer Files Server to Server using PHP Copy
	 *
	 */
	public static function copy( string $source_url, string $remote_file_url ) {

		/* New file name and path for this file */
		$local_file = str_replace( $source_url, ABSPATH, $remote_file_url );

		/* Copy the file from source url to server */
		$copy = copy( $remote_file_url, $local_file );

		/* Add notice for success/failure */
		if ( ! $copy ) {
			$log = new Logs( 'ERROR: Failed to copy $remote_file_url.', true );
			unset( $log );
			return false;
		}

		return true;

	}

}