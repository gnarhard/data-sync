<?php


namespace DataSync\Controllers;

use DataSync\Models\SyncedTerm;
use stdClass;

/**
 * Class SyncedTaxonomies
 * @package DataSync\Controllers
 */
class SyncedTerms
{

    /**
     * SyncedTaxonomies constructor.
     *
     * Creates/Updates all terms sent from source.
     *
     * @param int $post_id
     * @param object $taxonomies
     */
    public function __construct()
    {
        // NOTHING RIGHT NOW.
    }

    public static function save(object $source_data)
    {
        $prepared_data = self::prep($source_data);

        if (isset($prepared_data->id)) {
            SyncedTerm::update($prepared_data);
        } else {
            $new_id = SyncedTerm::create($prepared_data);
            if (is_numeric($new_id)) {
                $prepared_data->id = $new_id;
            }
        }

        return wp_parse_args($prepared_data);
    }


    public static function prep($new_data)
    {
        $existing_receiver_term = get_term_by('slug', $new_data->slug, $new_data->taxonomy);
        $existing_synced_term   = SyncedTerm::get_where([ 'source_term_id' => $new_data->term_id ]);

        $data                   = new stdClass();
        $data->slug             = $new_data->slug;
        $data->receiver_site_id = get_option('data_sync_receiver_site_id');
        $data->receiver_term_id = $existing_receiver_term->term_id;
        $data->source_term_id   = $new_data->term_id;
        $data->source_parent_id = $new_data->parent;
        $data->diverged         = false; // TODO: ADDRESS THIS IN FUTURE.

        if (! empty($existing_synced_term)) {
            $data->id = $existing_synced_term[0]->id;
        }

        return $data;
    }


    public static function save_to_wp(int $post_id, object $taxonomies)
    {

        foreach ($taxonomies as $taxonomy_slug => $taxonomy_data) {
            if (false !== $taxonomy_data) {

                // REMOVE ALL TERMS FROM POST.
                wp_set_object_terms($post_id, null, $taxonomy_slug);

                foreach ($taxonomy_data as $term) {
                    $new_term = wp_set_object_terms($post_id, $term->name, $taxonomy_slug, true);

                    if (! is_wp_error($new_term)) {
                        $new_synced_term = SyncedTerms::save($term);
                    } else {
                        $registered_taxonomies = get_taxonomies();
                        $logs = new Logs();
                        $logs->set('Term: ' . $term->slug . ' failed to connect to post. ' . $new_term->get_error_message(), true);
                        return $new_term;
                    }
                }
            }
        }

        SyncedTerms::update();

    }


    public static function update() {

        $synced_terms = SyncedTerm::get_all();

        foreach ($synced_terms as $synced_term) {

            // GET RECEIVER TERM.
            $receiver_term = get_term($synced_term->receiver_term_id);

            // CHECK IF SYNCED TERM PARENT ID IS 0 - MEANING NO PARENT.
            if (0 !== (int) $synced_term->source_parent_id) {

                // GET PARENT SYNCED TERM ID TO FIND PARENT RECEIVER TERM.
                $parent_synced_term = SyncedTerm::get_where([ 'source_term_id' => $synced_term->source_parent_id ])[0];

                // GET RECEIVER PARENT TERM.
                $parent_receiver_term = get_term($parent_synced_term->receiver_term_id);

                $args = array(
                    'parent' => (int) $parent_receiver_term->term_id,
                );

                wp_update_term((int) $synced_term->receiver_term_id, $receiver_term->taxonomy, $args);
            }
        }

    }


    public function delete()
    {
        // TODO: BUILD THIS
    }
}
