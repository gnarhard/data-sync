<?php
function wp_data_sync_enabled_post_types_widget() {
	?>
	<table>
		<thead>
		</thead>
		<tr>
			<th><?php _e( 'Post Type', 'wp_data_sync' )?></th>
			<th><?php _e( 'Enabled', 'wp_data_sync' )?></th>
		</tr>
		<?php
		$registered_post_types = get_post_types();
		foreach ($registered_post_types as $key => $post_type) {
			?>
			<tr>
				<td><?php echo $post_type?></td>
				<td><input type="checkbox" value="1" name="enabled_post_type_<?php echo $key?>" /></td>
			</tr>
			<?php
		}
		?>
	</table>
	<button id="save_enabled_post_types">Save</button>
	<?php
}
