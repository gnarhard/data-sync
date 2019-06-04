<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;

function add_canonical_radio_inputs( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_canonical_sites', true );

	?>
	<input type="radio" id="canonical_sites" name="canonical_sites" value="0" size="25" <?php checked( $value, 0 ); ?>/>
	<label for="canonical_sites">
		<?php _e( 'Source', 'textdomain' ); ?>
	</label>

	<?php
	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	if ( is_array( $connected_sites ) ) {
		foreach ( $connected_sites as $site ) {
			?>
			<br>
			<input type="radio" id="canonical_sites" name="canonical_sites" value="<?php echo $site->id ?>"
			       size="25" <?php checked( $value, $site->id ); ?>/>
			<label for="canonical_sites">
				<?php _e( $site->name, 'textdomain' ); ?>
			</label>
			<?php
		}
	}
}

function add_excluded_sites_select_field( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_canonical_sites', true );

	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	?>
	<select name="excluded_sites[]" multiple id="excluded_sites">
		<?php
		if ( is_array( $connected_sites ) ) {
			foreach ( $connected_sites as $site ) {
				?>
				<option value="<?php echo $site->id; ?>" <?php selected( in_array( $site->id, get_option( 'excluded_sites' ) ) ); ?> style="width: 100%; min-width: 400px;"><?php echo $site->name; ?></option>
				<?php
			}
		}
		?>
	</select>
	<?php

}