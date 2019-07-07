<?php


namespace DataSync\Models;

use DataSync\Models\DB;
use DataSync\Helpers;

class Log {

	public static $table_name = 'data_sync_connected_sites';

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

	public static function create( $data ) {
		$url = Helpers::format_url( $data['url'] );

		$args    = array(
			'message'           => $data->message,
			'url'            => Helpers::format_url( $data->url ),
			'datetime' => current_time( 'mysql' ),
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

		$url = Helpers::format_url( $data['url'] );

		$args = array(
			'id'             => $data->id,
			'message'           => $data->message,
			'url'            => Helpers::format_url( $data->url ),
			'datetime' => current_time( 'mysql' ),
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
	        message              longtext,
	        url               VARCHAR(255) NOT NULL,
	        datetime    DATETIME NOT NULL 
	    );'
		);
	}

}