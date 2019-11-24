<?php


namespace DataSync\Controllers;

/**
 * Class Enqueue
 * @package DataSync
 */
class Enqueue
{

    /**
     * Enqueues scripts and styles
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [ $this, 'admin_styles' ]);
        add_action('admin_enqueue_scripts', [ $this, 'admin_scripts' ]);
    }

    /**
     * Enqueues scripts
     */
    public function admin_scripts( $hook_suffix )
    {
        wp_register_script('data-sync-admin', DATA_SYNC_URL . 'public/views/assets/dist/js/admin-autoloader.es6.js', array( 'jquery' ), 1, true);

        $localized_data = array(
            'strings' => array(
                'saved' => __('Options Saved', 'text-domain'),
                'error' => __('Error', 'text-domain'),
            ),
            'api'     => array(
                'url'   => esc_url_raw(rest_url(DATA_SYNC_API_BASE_URL)),
                'nonce' => wp_create_nonce('wp_rest'),
            ),
            'options' => array(
                'enabled_post_types' => (array) get_option('enabled_post_types'),
                'source_site'        => (bool) get_option('source_site'),
                'debug'              => (bool) get_option('debug'),
            ),
        );

        wp_localize_script('data-sync-admin', 'DataSync', $localized_data);

        if ('toplevel_page_data-sync-options' === $hook_suffix) {
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-datepicker');
        }

        wp_enqueue_script('data-sync-admin');
    }

    /**
     * Enqueues styles
     */
    public function admin_styles()
    {
        wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        wp_register_style('data-sync-admin', DATA_SYNC_URL . 'public/views/assets/dist/styles/data-sync.css', false, 1);
        wp_enqueue_style('data-sync-admin');
    }
}
