<?php


namespace DataSync\Models;


use WP_Error;

class SyncedPost {

	public static $table_name = 'data_sync_posts';

	public static function get( int $id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . ConnectedSite::$table_name . ' WHERE id = %d', $id ) );
	}

	public static function get_where( array $args ) {
		global $wpdb;

		$query     = 'SELECT * FROM ' . $wpdb->prefix . self::$table_name . ' WHERE';
		$arg_count = count( $args );
		$i         = 1;

		foreach ( $args as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT );
			} else {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_STRING );
			}
			$query .= ' ' . $key . ' = ' . $filtered_value;
			if ( $i < $arg_count ) {
				$query .= ' AND';
			}
			$i ++;
		}

		return $wpdb->get_results( $query );

	}

	public static function create( object $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;

		$result = $wpdb->insert(
			$table_name,
			array(
				'source_post_id'   => $data->source_post_id,
				'receiver_post_id' => $data->receiver_post_id,
				'site_id'          => $data->receiver_site_id,
				'name'             => $data->name,
				'date_modified'    => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);

		if ( $result ) {
			return $wpdb->insert_id;
		} else {
			// TODO: ERROR HANDLING
			return false;
		}
	}

	public static function update( $data ) {
		print_r( $data );
		global $wpdb;
		$db_data                     = array();
		$db_data['id']               = $data->id;
		$db_data['name']             = $data->name;
		$db_data['source_post_id']   = $data->source_post_id;
		$db_data['receiver_post_id'] = $data->receiver_post_id;
		$db_data['site_id']          = $data->receiver_site_id;
//		$db_data['date_modified']    = current_time( 'mysql' );

		print_r( $db_data );

		$updated = $wpdb->update( $wpdb->prefix . self::$table_name, $db_data, [ 'id' => $data->id ] );
		var_dump( $updated );

		if ( false === $updated ) {
			// TODO: BETTER ERRORS
			$error_message = $wpdb->print_error();
			echo $error_message;

			return new WP_Error( 503, __( $error_message, 'data-sync' ) );
		} else {
			return $updated;
		}
	}

	public static function delete( $id ) {
		//TODO: PROCESS DELETED POSTS
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

	public function create_db_table() {

		global $wpdb;
		$charset_collate = preg_replace( '/DEFAULT /', '', $wpdb->get_charset_collate() );

		$result = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        source_post_id     INT NOT NULL,
	        receiver_post_id   INT NOT NULL,
	        site_id            INT NOT NULL,
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
			ADD CONSTRAINT fk_site_id FOREIGN KEY (site_id) 
			REFERENCES ' . $wpdb->prefix . ConnectedSite::$table_name . '(id)
			ON DELETE CASCADE
			ON UPDATE CASCADE
			;'
		);
	}

}