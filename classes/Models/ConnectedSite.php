<?php


namespace DataSync\Models;

use DataSync\Models\DB;
use DataSync\Helpers;

class ConnectedSite
{
    public static $table_name = 'data_sync_connected_sites';

    public static function get(int $id)
    {
        $db = new DB(self::$table_name);

        return $db->get($id);
    }

    public static function get_all()
    {
        $db = new DB(self::$table_name);

        return $db->get_all();
    }

    public static function get_where(array $args)
    {
        $db = new DB(self::$table_name);

        return $db->get_where($args);
    }

    public static function create($data)
    {
        $url = Helpers::format_url($data['url']);
        $local_timestamp = date( 'g:i:s A n/d/Y', get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $data['sync_start'] ) ), 'U' ) );

        $args    = array(
            'name'           => sanitize_text_field($data['name']),
            'url'            => esc_url_raw($url),
            'secret_key'     => sanitize_text_field($data['secret_key']),
            'sync_start'     => $data['sync_start'],
            'date_connected' => current_time('mysql'),
        );
        $sprintf = array(
            '%s',
            '%s',
            '%s',
        );

        $db = new DB(self::$table_name);

        return $db->create($args, $sprintf);
    }

    public static function update(object $data)
    {
        $url = Helpers::format_url($data['url']);

        $args = array(
            'id'             => $data->id,
            'name'           => sanitize_text_field($data->name),
            'url'            => Helpers::format_url($data->url),
            'secret_key'     => sanitize_text_field($data->secret_key),
            'sync_start'     => $data['sync_start'],
            'date_connected' => current_time('mysql'),
        );

        $where = [ 'id' => $data->id ];

        $db = new DB(self::$table_name);

        return $db->update($args, $where);
    }

    public static function delete($id)
    {
        $db = new DB(self::$table_name);

        return $db->delete($id);
    }

    public function create_db_table()
    {
        global $wpdb;

        $charset_collate = preg_replace('/DEFAULT /', '', $wpdb->get_charset_collate());

        $result = $wpdb->query(
            'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::$table_name . ' (
	        id INT NOT NULL AUTO_INCREMENT,
	        PRIMARY KEY(id),
	        name              VARCHAR(255),
	        url               VARCHAR(255) NOT NULL,
	        secret_key        VARCHAR(255) NOT NULL,
	        sync_start        DATETIME NOT NULL,
	        date_connected    DATETIME NOT NULL 
	    );'
        );

        $this->add_foreign_key_restraints();
    }

    private function add_foreign_key_restraints()
    {
        global $wpdb;

        $charset_collate = preg_replace('/DEFAULT /', '', $wpdb->get_charset_collate());
        $result          = $wpdb->query(
            'ALTER TABLE ' . $wpdb->prefix . self::$table_name . '
			CONVERT TO ' . $charset_collate . ';'
        );
    }
}
