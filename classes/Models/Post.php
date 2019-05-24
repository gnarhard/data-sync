<?php


namespace DataSync\Models;


class Post {

	public static $table_name = 'data_sync_posts';

//	public static function create( $data ) {
//		global $wpdb;
//		$table_name = $wpdb->prefix . self::$table_name;
//
//		$result = $wpdb->insert(
//			$table_name,
//			array(
//				'name'           => $data['name'],
//				'date_created' => current_time( 'mysql' ),
//			),
//			array(
//				'%s',
//				'%s',
//			)
//		);
//
//		if ( $result ) {
//			return $wpdb->insert_id;
//		}
//	}
//
//	public static function update() {
//		global $wpdb;
//
////		$updated = $wpdb->update( $table, $data, $where );
//	}
//
//	public static function delete( $id ) {
//		global $wpdb;
//		$table_name = $wpdb->prefix . self::$table_name;
//		$result     = $wpdb->delete(
//			$table_name,
//			array(
//				'id' => $id,
//			),
//			array(
//				'%d',
//			)
//		);
//
//		return $result;
//
//	}
//
//	public static function create_db_table() {
//		global $wpdb;
//		$table_name = $wpdb->prefix . self::$table_name;
//
//		$result = $wpdb->query(
//			'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
//	        id INT NOT NULL AUTO_INCREMENT,
//	        PRIMARY KEY(id),
//	        name              VARCHAR(255),
//	        date_created    DATETIME NOT NULL
//	    );'
//		);
//	}

}