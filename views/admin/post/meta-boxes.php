<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;

function add_canonical_radio_inputs( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_canonical_site', true );

	?>
    <!--	<input type="radio" id="canonical_site" name="canonical_site" value="0" size="25" --><?php //checked( $value, 0 ); ?><!--/>-->
    <!--	<label for="canonical_site">None</label>-->

	<?php
	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	if ( is_array( $connected_sites ) ) {
		foreach ( $connected_sites as $site ) {
			?>
            <br>
            <input type="radio" id="canonical_site" name="canonical_site" value="<?php echo esc_html( $site->id ); ?>"
                   size="25" <?php checked( $value, $site->id ); ?>/>
            <label for="canonical_site">
				<?php echo esc_html( $site->name ); ?>
            </label>
			<?php
		}
	}
}

function add_excluded_sites_select_field( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_excluded_sites' );

	if ( count( $value ) ) {
		$value = $value[0];
	}

	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	?>
    <select name="excluded_sites[]" multiple id="excluded_sites" style="width: 100%; min-height: 200px;">
        <option value="0" <?php selected( in_array( 0, $value ) ); ?>>None</option>
		<?php
		if ( is_array( $connected_sites ) ) {
			foreach ( $connected_sites as $site ) {
				?>
                <option value="<?php echo $site->id; ?>" <?php selected( in_array( $site->id, $value ) ); ?>><?php echo $site->name; ?></option>
				<?php
			}
		}
		?>
    </select>
	<?php

}


function add_override_post_yoast_checkbox( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_override_post_yoast', true );
	?>
    <input type="checkbox" value="1" name="override_post_yoast" <?php checked( '1', $value ); ?>/>
    <label for="override_post_yoast">Update Yoast settings on receiver with next sync</label>
	<?php
}