<?php


namespace DataSync\Models;

use DataSync\Controllers\Error;
use WP_Error;
use WP_Query;

class ConnectedSite {

	public static $table_name = 'data_sync_connected_sites';

	public static function create( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;

		$url        = $data['url'];
		$parsed_url = wp_parse_url( $url );
		if ( ! isset( $parsed_url['scheme'] ) ) {
			$url = 'https://' . $url;
		}

		$exploded_url = explode( '.', $url );
		if ( ! isset( $exploded_url[1] ) ) {
			$error = new Error();
			( $error ) ? $error->log( 'Connected site was not saved.' . "\n" ) : null;
			return new WP_Error( 'database_error', 'DB Error: Connected site was not saved.', array( 'status' => 501 ) );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'name'           => $data['name'],
				'url'            => esc_url_raw( $url ),
				'date_connected' => self::get_timestamp(),
			),
			array(
				'%s',
				'%s',
			)
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}
	}

	public static function update() {
		global $wpdb;

//		$updated = $wpdb->update( $table, $data, $where );
	}

	public static function delete( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;
		$result     = $wpdb->delete(
			$table_name,
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		return $result;

	}

	public static function create_db_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;

		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        name              VARCHAR(255),
	        url               VARCHAR(255),
	        date_connected    DATETIME NOT NULL
	    );'
		);
	}

	private static function get_timestamp() {
		return current_time( 'mysql' );
	}

}