<?php


namespace DataSync\Controllers;

class Users
{
    public static function get_receiver_user_id($source_user_id, $users)
    {
        foreach ($users as $source_user) {
            if ((int) $source_user_id === (int) $source_user->ID) {
                $receiver_user = get_user_by('slug', $source_user->data->user_login);

                if (! $receiver_user) {
                    $receiver_user_id = wp_insert_user($source_user->data);
                    if (! is_wp_error($receiver_user_id)) {
                        return $receiver_user_id;
                    } else {
                        new Logs('New user was not created.', true);
                    }
                } else {
                    return $receiver_user->ID;
                }
            }
        }
    }
}
