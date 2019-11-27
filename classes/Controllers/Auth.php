<?php


namespace DataSync\Controllers;

use DataSync\Models\ConnectedSite;
use Exception;
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

class Auth
{

    /**
     * @var array
     *
     * Will contain username and password for authorized user
     */
    private $logins = array();

    public function __construct()
    {
    }

    public function verify_user($result)
    {
        if (! empty($result)) {
            return $result;
        }
        if (! is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ));
        }

        return $result;
    }

    public function get_site_secret_key(int $receiver_site_id)
    {
        if ($receiver_site_id) {
            $connected_site_data = ConnectedSite::get($receiver_site_id)[0];

            return $connected_site_data->secret_key;
        } else {
            $logs = new Logs();
            $logs->set('$receiver_site_id not set trying to get site secret key.', true);

            return false;
        }
    }

    public function prepare($data, $secret_key)
    {
        if ((! property_exists($data, 'receiver_site_id')) || (null === $data->receiver_site_id)) {
            $data->receiver_site_id = get_option('data_sync_receiver_site_id');
        }

        $json_decoded_data = json_decode(wp_json_encode($data)); // DO THIS TO MAKE SIGNATURE CONSISTENT. JSON DOESN'T RETAIN OBJECT CLASS TITLES.
        $data->sig         = (string) $this->create_signature($json_decoded_data, $secret_key);

        return wp_json_encode($data);
    }

    public static function authorize()
    {
        $data = (object) json_decode(file_get_contents('php://input'));
        $auth = new Auth();

        if (get_option('secret_key')) {

            // Get secret key option if receiver is trying to authorize source.
            return $auth->verify_signature($data, get_option('secret_key'));
        } elseif ((property_exists($data, 'receiver_site_id')) && (null !== $data->receiver_site_id)) {

            // Get secret key of connected site if source is trying to authorize a request from a receiver.
            $secret_key_of_receiver = $auth->get_site_secret_key($data->receiver_site_id);

            return $auth->verify_signature($data, $secret_key_of_receiver);
        }

        $error_msg = 'Failed to authorize cross-site connection.';
        $error_msg.= '<br>Data package: ' . wp_json_encode($data);
        $error_msg.= '<br>JSON: ' .  file_get_contents('php://input');
        $logs = new Logs();
        $logs->set($error_msg, true);

        return false;
    }

    /**
     * Check request permissions
     *
     * @return bool
     */
    public static function permissions(WP_REST_Request $request)
    {
        if (current_user_can('manage_options')) {
            return true;
        } else {
            if ($request->get_param('nonce')) {
                // TODO: THIS WON'T WORK WITH CORS.
                return wp_verify_nonce($request->get_param('nonce'), 'data_sync_api');
            }
        }
    }

    /**
     * Generate a signature string for the supplied data given a key.
     *
     * @param object $data
     * @param string $key
     *
     * @return string
     */
    public function create_signature($data, string $key)
    {
        if (isset($data->sig)) {
            unset($data->sig);
        }

        return base64_encode(hash_hmac('sha1', serialize($data), $key, true));
    }


    public function verify_signature($data, string $key)
    {
        if (empty($data->sig)) {
            return false;
        }

        $signature_sent     = $data->sig;
        $signature_received = $this->create_signature($data, $key);

        return $signature_received === $signature_sent;
    }


    public function generate_key($length = 40)
    {
        $keyset = 'abcdefghijklmnopqrstuvqxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/';
        $key    = '';

        for ($i = 0; $i < $length; $i ++) {
            $key .= substr($keyset, wp_rand(0, strlen($keyset) - 1), 1);
        }

        return $key;
    }

    public function sanitize_signature_data($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return $value;
    }
}
