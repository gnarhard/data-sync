<?php


namespace DataSync\Models;


use DataSync\Controllers\Logs;
use DataSync\Helpers;
use WP_Error;

/**
 * Class DB
 * @package DataSync\Models
 */
class DB {

	/**
	 * @var string
	 */
	public $table_name;

	/**
	 * DB constructor.
	 *
	 * @param $table_name
	 */
	public function __construct( $table_name = '' ) {
		global $wpdb;
		$this->table_name = $wpdb->prefix . $table_name;
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function get( int $id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table_name . ' WHERE id = %d', $id ) );
	}


	public function get_all() {
		global $wpdb;

		return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function get_where( array $args ) {
		global $wpdb;

		$query     = 'SELECT * FROM ' . $this->table_name . ' WHERE';
		$arg_count = count( $args );
		$i         = 1;

		foreach ( $args as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT );
				$query          .= ' `' . $key . '` = ' . $filtered_value;
			} else {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_STRING );
				$query          .= ' `' . $key . '` = \'' . $filtered_value . '\'';
			}

			if ( $i < $arg_count ) {
				$query .= ' AND';
			}
			$i ++;
		}

		return $wpdb->get_results( $query );

	}

	/**
	 * @param $args
	 * @param $sprintf
	 *
	 * @return WP_Error
	 */
	public function create( array $args, array $sprintf ) {
		global $wpdb;

		$created = $wpdb->insert(
			$this->table_name,
			$args,
			$sprintf
		);

//		echo $wpdb->last_query;
		if ( false === $created ) {
			$error_msg = 'DB insert failed: ' . $wpdb->last_error;
			$log       = new Logs( $error_msg, true );
			unset( $log );

			return new WP_Error( 503, __( $error_msg, 'data-sync' ) );
		} else {

			return $created;
		}
	}

	/**
	 * @param $args
	 * @param $where
	 *
	 * @return WP_Error
	 */
	public function update( array $args, $where ) {
		global $wpdb;

		$updated = $wpdb->update( $this->table_name, $args, $where );

		if ( false === $updated ) {
			$error_msg = 'Database failed to update: ' . $wpdb->last_error;
			$log       = new Logs( $error_msg, true );
			unset( $log );

			return new WP_Error( 503, __( $error_msg, 'data-sync' ) );
		} else {
			return $updated;
		}
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function delete( int $id ) {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table_name,
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		return $result;

	}

	/**
	 *
	 * @return mixed
	 */
	public function delete_all() {
		global $wpdb;
		$sql    = 'TRUNCATE TABLE ' . $this->table_name;
		$result = $wpdb->get_results( $sql );

		if ( empty( $result ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function query( string $sql ) {
		global $wpdb;

		$result = $wpdb->get_results( $sql );

		return $result;
	}


}