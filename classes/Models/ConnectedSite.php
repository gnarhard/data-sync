<?php


namespace DataSync\Models;

use WP_Query;

class ConnectedSite {

	public static function create() {


	}

	public static function create_db_table() {
		$query = 'CREATE TABLE IF NOT EXISTS data_sync_connected_sites (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        name              VARCHAR(255),
	        url               VARCHAR(255),
	        date_connected    DATE NOT NULL,
	    );';
	}

	public static function save() {
		global $wpdb;
		print_r($wpdb->tables);
	}

	public static function update() {

	}

	public static function delete() {

	}

}