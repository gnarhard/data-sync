<?php


namespace DataSync\Models;


class PostType {

	public static $table_name = 'data_sync_custom_post_types';

	public static function create( object $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;

		$result = $wpdb->insert(
			$table_name,
			array(
				'name'         => $data->name,
				'data'         => wp_json_encode( $data ),
				'date_created' => current_time( 'mysql' ),
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

	public static function update( object $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;

		$db_data         = array();
		$db_data['name'] = $data->name;
		$db_data['data'] = wp_json_encode( $data );

		$updated = $wpdb->update( $table_name, $db_data, [ 'id' => $data->id ] );

		if ( false === $updated ) {
			// TODO: RETURN ERROR
		} else {
			return $updated;
		}
	}

	public static function delete( int $id ) {
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
	        data              json,
	        date_created    DATETIME NOT NULL
	    );'
		);
	}

	public static function register_cpt() {
		// TODO: ADD PHP CODE FOR REGISTERING CPTs SAVED TO DB
	}

}