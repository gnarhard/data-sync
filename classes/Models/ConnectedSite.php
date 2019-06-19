<?php


namespace DataSync\Models;

use DataSync\Controllers\Error;
use DataSync\Helpers;
use WP_Error;
use WP_Query;

class ConnectedSite {

	public static $table_name = 'data_sync_connected_sites';

	public static function get( int $id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . ConnectedSite::$table_name . ' WHERE id = %d', $id ) );
	}

	public static function create( $data ) {
		global $wpdb;

		$url = Helpers::format_url( $data['url'] );

		$result = $wpdb->insert(
			$wpdb->prefix . self::$table_name,
			array(
				'name'           => $data['name'],
				'url'            => esc_url_raw( $url ),
				'secret_key'     => sanitize_text_field( $data['secret_key'] ),
				'date_connected' => current_time( 'mysql' ),
			),
			array(
				'%s',
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
		$result = $wpdb->delete(
			$wpdb->prefix . self::$table_name,
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		return $result;

	}

	public function create_db_table() {
		global $wpdb;

		$charset_collate = preg_replace( '/DEFAULT /', '', $wpdb->get_charset_collate() );

		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        name              VARCHAR(255),
	        url               VARCHAR(255) NOT NULL,
	        secret_key        VARCHAR(255) NOT NULL,
	        date_connected    DATETIME NOT NULL 
	    );'
		);

		$this->add_foreign_key_restraints();
	}

	private function add_foreign_key_restraints() {
		global $wpdb;

		$charset_collate = preg_replace( '/DEFAULT /', '', $wpdb->get_charset_collate() );
		$result = $wpdb->query(
			'ALTER TABLE ' . $wpdb->prefix . self::$table_name . '
			CONVERT TO ' . $charset_collate . ';'
		);
	}

}