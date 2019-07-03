<?php


namespace DataSync\Models;


use WP_Error;
use DataSync\Models\DB;

class SyncedPost {

	public static $table_name = 'data_sync_posts';

	public static function get( int $id ) {
		$db = new DB( self::$table_name );

		return $db->get( $id );
	}

	public static function get_where( array $args ) {
		$db = new DB( self::$table_name );

		return $db->get_where( $args );

	}

	public static function create( object $data ) {

		$args    = array(
			'source_post_id'   => $data->source_post_id,
			'receiver_post_id' => $data->receiver_post_id,
			'receiver_site_id' => $data->receiver_site_id,
			'name'             => $data->name,
			'date_modified'    => current_time( 'mysql' ),
		);
		$sprintf = array(
			'%d',
			'%d',
			'%d',
			'%s',
			'%s',
		);

		$db = new DB( self::$table_name );

		return $db->create( $args, $sprintf );
	}

	public static function update( $data ) {

		$args = array(
			'id'               => $data->id,
			'name'             => $data->name,
			'source_post_id'   => $data->source_post_id,
			'receiver_post_id' => $data->receiver_post_id,
			'receiver_site_id' => $data->receiver_site_id,
			'date_modified'    => current_time( 'mysql' ),
		);

		$where = [ 'id' => $data->id ];
		$db    = new DB( PostType::$table_name );

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
	        source_post_id     INT NOT NULL,
	        receiver_post_id   INT NOT NULL,
	        receiver_site_id            INT NOT NULL,
	        name              VARCHAR(255) NOT NULL,
	        date_modified    DATETIME NOT NULL
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

		$result = $wpdb->query(
			'ALTER TABLE ' . $wpdb->prefix . self::$table_name . ' 
			ADD CONSTRAINT fk_receiver_site_id FOREIGN KEY (receiver_site_id) 
			REFERENCES ' . $wpdb->prefix . ConnectedSite::$table_name . '(id)
			ON DELETE CASCADE
			ON UPDATE CASCADE
			;'
		);
	}

}