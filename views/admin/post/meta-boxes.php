<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;

function add_canonical_radio_inputs( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_canonical_site', true );

	?>
	<input type="radio" id="canonical_site" name="canonical_site" value="0" size="25" <?php checked( $value, 0 ); ?>/>
	<label for="canonical_site">Source</label>

	<?php
	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	if ( is_array( $connected_sites ) ) {
		foreach ( $connected_sites as $site ) {
			?>
			<br>
			<input type="radio" id="canonical_site" name="canonical_site" value="<?php echo esc_html( $site->id ); ?>" size="25" <?php checked( $value, $site->id ); ?>/>
			<label for="canonical_site">
				<?php echo esc_html( $site->name ); ?>
			</label>
			<?php
		}
	}
}

function add_excluded_sites_select_field( $post ) {
	wp_nonce_field( 'data_sync_post_meta_box', 'data_sync_post_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_excluded_sites' )[0];

	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;
	?>
	<select name="excluded_sites[]" multiple id="excluded_sites" style="width: 100%; min-width: 400px; min-height: 200px;">
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