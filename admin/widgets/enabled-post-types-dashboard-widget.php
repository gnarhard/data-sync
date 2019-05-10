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


add_action('wp_ajax_update_enabled_post_types', 'update_enabled_post_types');
function update_enabled_post_types() {
  print_r($_POST);
//	update_option( 'enabled_post_types', $new_value );
}