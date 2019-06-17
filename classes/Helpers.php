<?php

namespace DataSync;


use DataSync\Controllers\Error;
use WP_Error;

class Helpers {

	public static function format_url( $url ) {

		$parsed_url = wp_parse_url( $url );
		if ( ! isset( $parsed_url['scheme'] ) ) {
			$url = 'https://' . $url;
		}

		$url = preg_replace("/^http:/i", "https:", $url);

		$exploded_url = explode( '.', $url );
		if ( ! isset( $exploded_url[1] ) ) {
			$error = new Error();
			( $error ) ? $error->log( 'Connected site was not saved.' . "\n" ) : null;
			return new WP_Error( 'database_error', 'DB Error: Connected site was not saved.', array( 'status' => 501 ) );
		}

		return $url;
	}

}