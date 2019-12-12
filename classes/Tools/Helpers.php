<?php

namespace DataSync\Tools;

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
			$logs = new Logs();
			$logs->set( 'ERROR: Connected site url could not be processed.', true );

			return new WP_Error( 'database_error', 'DB Logs: Connected site was not saved.', array( 'status' => 501 ) );
		}

		return $url;
	}

	/**
	 * @param $obj
	 *
	 * Recursively convert an object into an array
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

	/**
	 * Tests if an input is valid PHP serialized string.
	 *
	 * Checks if a string is serialized using quick string manipulation
	 * to throw out obviously incorrect strings. Unserialize is then run
	 * on the string to perform the final verification.
	 *
	 * Valid serialized forms are the following:
	 * <ul>
	 * <li>boolean: <code>b:1;</code></li>
	 * <li>integer: <code>i:1;</code></li>
	 * <li>double: <code>d:0.2;</code></li>
	 * <li>string: <code>s:4:"test";</code></li>
	 * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
	 * <li>object: <code>O:8:"stdClass":0:{}</code></li>
	 * <li>null: <code>N;</code></li>
	 * </ul>
	 *
	 * @param string $value Value to test for serialized form
	 * @param mixed $result Result of unserialize() of the $value
	 *
	 * @return        boolean            True if $value is serialized data, otherwise false
	 * @author        Chris Smith <code+php@chris.cs278.org>
	 * @copyright    Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	 * @license        http://sam.zoy.org/wtfpl/ WTFPL
	 */
	public static function is_serialized( $value, &$result = null ) {
		if ( '' === $value ) {
			return false;
		}

		// Bit of a give away this one
		if ( ! is_string( $value ) ) {
			return false;
		}

		// Serialized false, return true. unserialize() returns false on an
		// invalid string or it could return false if the string is serialized
		// false, eliminate that possibility.
		if ( $value === 'b:0;' ) {
			$result = false;

			return true;
		}
		$length = strlen( $value );
		$end    = '';

		switch ( $value[0] ) {
			case 's':
				if ( $value[ $length - 2 ] !== '"' ) {
					return false;
				}
			case 'b':
			case 'i':
			case 'd':
				// This looks odd but it is quicker than isset()ing
				$end .= ';';
			case 'a':
			case 'O':
				$end .= '}';
				if ( $value[1] !== ':' ) {
					return false;
				}
				switch ( $value[2] ) {
					case 0:
					case 1:
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
					case 8:
					case 9:
						break;
					default:
						return false;
				}
			case 'N':
				$end .= ';';
				if ( $value[ $length - 1 ] !== $end[0] ) {
					return false;
				}
				break;
			default:
				return false;
		}


		if ( ( $result = @unserialize( $value ) ) === false ) {
			$result = null;

			return false;
		}

		return true;
	}
}
