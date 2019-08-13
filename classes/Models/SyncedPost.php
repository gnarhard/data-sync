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

	public static function get_all() {
		$db           = new DB( self::$table_name );
		$synced_posts = $db->get_all();

		foreach ( $synced_posts as $index => $synced_post ) {
			$post = get_post( $synced_post->source_post_id );
			if ( 'trash' === $post->post_status ) {
				unset( $synced_posts[ $index ] );
			}
		}

		return $synced_posts;
	}

	public static function get_where( array $args ) {
		$db = new DB( self::$table_name );

		return $db->get_where( $args );
	}

	public static function get_all_and_sort( $sortby, $data_sync_start_time = false ) {

		global $wpdb;
		$column = array_key_first( $sortby );
		$order  = $sortby[ $column ];
		$sql    = 'SELECT * FROM ' . $wpdb->prefix . self::$table_name . ' WHERE date_modified > "' . $data_sync_start_time . '" ORDER BY ' . $column . ' ' . $order;

		$db = new DB( self::$table_name );

		return $db->query( $sql );
	}

	public static function create( object $data ) {

		$args = array(
			'source_post_id'   => $data->source_post_id,
			'receiver_post_id' => $data->receiver_post_id,
			'receiver_site_id' => $data->receiver_site_id,
			'name'             => $data->name,
			'post_type'        => $data->post_type,
			'diverged'         => 0,
		);

		if ( isset( $data->date_modified ) ) {
			$args['date_modified'] = $data->date_modified;
		} else {
			$args['date_modified'] = current_time( 'mysql' );
		}

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
			'post_type'        => $data->post_type,
			'diverged'         => $data->diverged,
		);

		if ( isset( $data->date_modified ) ) {
			$args['date_modified'] = $data->date_modified;
		} else {
			$args['date_modified'] = current_time( 'mysql' );
		}

		$where = [ 'id' => $data->id ];
		$db    = new DB( self::$table_name );

		return $db->update( $args, $where );

	}

	public static function delete( $id ) {
		$db = new DB( self::$table_name );

		return $db->delete( $id );

	}

	public function create_db_table_source() {

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
	        post_type       VARCHAR(255) NOT NULL,
	        diverged        TINYINT(1),
	        date_modified    DATETIME NOT NULL
	    );'
		);

		$this->add_foreign_key_restraints();
	}

	public function create_db_table_receiver() {

		global $wpdb;

		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        source_post_id     INT NOT NULL,
	        receiver_post_id   INT NOT NULL,
	        receiver_site_id            INT NOT NULL,
	        name              VARCHAR(255) NOT NULL,
	        post_type       VARCHAR(255) NOT NULL,
	        diverged        TINYINT(1),
	        date_modified    DATETIME NOT NULL
	    );'
		);

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