<?php


namespace DataSync\Models;

use DataSync\Models\DB;

class SyncedTerm {

	public static $table_name = 'data_sync_terms';

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

		$args = array(
			'slug'             => $data->slug,
			'source_term_id'   => $data->source_term_id,
			'receiver_term_id' => $data->receiver_term_id,
			'receiver_site_id' => $data->receiver_site_id,
			'source_parent_id' => $data->source_parent_id,
			'diverged'         => $data->diverged,
			'date_modified'    => current_time( 'mysql', 1 ),
		);

		$sprintf = array(
			'%s',
			'%d',
			'%d',
			'%d',
			'%d',
			'%s',
		);

		$db = new DB( self::$table_name );

		return $db->create( $args, $sprintf );
	}

	public static function update( object $data ) {

		$args = array(
			'id'               => $data->id,
			'slug'             => $data->slug,
			'source_term_id'   => $data->source_term_id,
			'receiver_term_id' => $data->receiver_term_id,
			'receiver_site_id' => $data->receiver_site_id,
			'source_parent_id' => $data->source_parent_id,
			'diverged'         => $data->diverged,
			'date_modified'    => current_time( 'mysql', 1 ),
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
		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        slug              VARCHAR(255) NOT NULL,
	        source_term_id     INT NOT NULL,
	        receiver_term_id   INT NOT NULL,
	        source_parent_id   INT,
	        receiver_site_id            INT NOT NULL,
	        diverged        TINYINT(1),
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
			ADD CONSTRAINT fk_receiver_term_id FOREIGN KEY (receiver_term_id) 
			REFERENCES ' . $wpdb->prefix . 'wp_posts' . '(id)
			ON DELETE CASCADE
			ON UPDATE CASCADE
			;'
		);
	}

}