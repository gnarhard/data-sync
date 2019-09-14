<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;

class File {

	/**
	 * Transfer Files Server to Server using PHP Copy
	 *
	 */
	public static function copy( object $source_data, $file_contents = false ) {

		$upload_dir = wp_get_upload_dir();
		$ext        = pathinfo( $source_data->filename, PATHINFO_EXTENSION );

		/* New file name and path for this file */
		if ( ( 'php' === $ext ) && ( $file_contents ) ) {
			// TEMPLATE FILE.
			$local_file = (string) str_replace( $source_data->source_upload_url, DATA_SYNC_PATH . 'templates/', $source_data->media->guid );
		} else {
			// MEDIA FILE.
			$local_file = (string) str_replace( $source_data->source_upload_url, $upload_dir['path'], $source_data->media->guid );
		}


		var_dump( 'asdf' );
		var_dump( $source_data->filename );
		var_dump( $source_data->media->guid );
		var_dump( $local_file );
		var_dump( $source_data->source_upload_url );
		var_dump( $upload_dir['path'] );

		// CHECK DIRECTORIES EXIST.
		mkdir( dirname( $local_file ), 0644, true );

		/* Copy the file from source url to server */
		$copied = copy( $source_data->media->guid, $local_file );

		/* Add notice for success/failure */
		if ( ! $copied ) {
			$log = new Logs( 'Failed to copy ' . $source_data->media->guid . '.', true );
			unset( $log );

			return false;
		}

		if ( ( 'php' === $ext ) && ( $file_contents ) ) {
			file_put_contents( $local_file, $file_contents );
		}

		return true;

	}

}