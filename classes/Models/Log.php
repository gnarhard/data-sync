<?php


namespace DataSync\Models;

use DataSync\Models\DB;
use DataSync\Helpers;

class Log {

	public static $table_name = 'data_sync_log';

	public function __construct() {
		$this->create_db_table();
	}

	public static function get( int $id ) {
		$db = new DB( self::$table_name );

		return $db->get( $id );
	}

	public static function get_where( array $args ) {
		$db = new DB( self::$table_name );

		return $db->get_where( $args );
	}

	public static function get_all_and_sort( $sortby ) {

		global $wpdb;
		$column = array_key_first( $sortby );
		$order  = $sortby[ $column ];

		$sql = 'SELECT * FROM ' . $wpdb->prefix . self::$table_name . ' ORDER BY ' . $column . ' ' . $order;
		$db  = new DB( self::$table_name );

		return $db->query( $sql );
	}

	public static function create( object $data ) {

		$args    = array(
			'log_entry'  => $data->log_entry,
			'url_source' => Helpers::format_url( $data->url ),
			'datetime'   => current_time( 'mysql' ),
		);
		$sprintf = array(
			'%s',
			'%s',
			'%s',
		);

		$db = new DB( self::$table_name );

		return $db->create( $args, $sprintf );

	}

	public static function update( object $data ) {

		$args = array(
			'id'         => $data->id,
			'log_entry'  => $data->log_entry,
			'url_source' => Helpers::format_url( $data->url ),
			'datetime'   => current_time( 'mysql' ),
		);

		$where = [ 'id' => $data->id ];

		$db = new DB( self::$table_name );

		return $db->update( $args, $where );
	}

	public static function delete( $id ) {
		$db = new DB( self::$table_name );

		return $db->delete( $id );
	}

	public function create_db_table() {
		global $wpdb;

		$charset_collate = preg_replace( '/DEFAULT /', '', $wpdb->get_charset_collate() );

		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        log_entry              longtext,
	        url_source               VARCHAR(255) NOT NULL,
	        datetime    DATETIME NOT NULL 
	    );'
		);
	}

}