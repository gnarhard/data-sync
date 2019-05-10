<?php

function wp_data_sync_enabled_post_types_widget() {
	$args = array(
		'public'   => true,
//            '_builtin' => false
	);
	$output = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$registered_post_types = get_post_types($args, $output, $operator);

	?><select name="enabled_post_types[]" multiple style="width: 200px;" id="enabled_post_types"><?php

	foreach ($registered_post_types as $key => $post_type) {

		$post_type_object = get_post_type_object( $post_type );
		?><option value="<?php echo $post_type_object->name?>" <?php selected( in_array($post_type_object->name, get_option( 'enabled_post_types' )) );?>><?php echo $post_type_object->label?></option><?php

	}

	?></select>

  <button id="save_enabled_post_types">Save</button>
    <?php
}


function DEP_wp_data_sync_enabled_post_types_widget() {
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
