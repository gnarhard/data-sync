<?php


namespace DataSync\Models;
use DataSync\Models\DB;

class ACF {

	public static $table_name = 'data_sync_acf';

	public static function get( int $id ) {
		$db = new DB( self::$table_name );

		return $db->get( $id );
	}

	public static function get_all() {
		$db = new DB( self::$table_name );

		return $db->get_all();
	}

	public static function get_where( array $args ) {
		$db = new DB( self::$table_name );

		return $db->get_where( $args );

	}

	public static function create( object $data ) {

		$args    = array(
			'name'         => $data->name,
			'receiver_id'  => $data->receiver_id,
			'data'         => wp_json_encode( $data ),
			'date_created' => current_time( 'mysql' ),
		);
		$sprintf = array(
			'%s',
			'%d',
			'%s',
			'%s',
		);

		$db = new DB( self::$table_name );

		return $db->create( $args, $sprintf );
	}

	public static function update( object $data ) {

		$args = array(
			'name'         => $data->name,
			'receiver_id'  => $data->receiver_id,
			'data'         => wp_json_encode( $data ),
			'date_created' => current_time( 'mysql' ),
		);

		$where = [ 'id' => $data->id ];

		$db = new DB( self::$table_name );

		return $db->update( $args, $where );
	}

	public static function delete( int $id ) {
		$db = new DB( self::$table_name );

		return $db->delete( $id );
	}

	public function create_db_table() {
		global $wpdb;
		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        receiver_id       INT NOT NULL,
	        name              VARCHAR(255),
	        data              json,
	        date_created    DATETIME NOT NULL
	    );'
		);
	}

}