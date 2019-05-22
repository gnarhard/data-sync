<?php namespace DataSync;

use WP_User_Query;
use DataSync\Error as Error;

/**
 *
 */
function display_source_input() {   ?>
	<input type="radio" name="source_site" id="source_site" value="1" <?php checked( '1', get_option( 'source_site' ) ); ?>/> Source
	<br>
	<input type="radio" name="source_site" id="source_site" value="0" <?php checked( '0', get_option( 'source_site' ) ); ?>/> Receiver
	<?php
}


/**
 *
 */
function display_push_template_button() {
	?>
	<button id="push_template">Push</button>
	<?php
}


/**
 *
 */
function display_push_enabled_post_types() {
	$args = array(
		'public' => true,
	// '_builtin' => false
	);

	$output   = 'names'; // Names or objects, note names is the default.
	$operator = 'and';

	$registered_post_types = get_post_types( $args, $output, $operator );
	?>
	<select name="push_enabled_post_types[]" multiple style="width: 200px;" id="push_enabled_post_types">\
		<?php

		foreach ( $registered_post_types as $key => $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			?>
			<option value="
			<?php echo esc_html( $post_type_object->name ); ?>"
				<?php esc_html( selected( in_array( $post_type_object->name, get_option( 'push_enabled_post_types' ), true ) ) ); ?>>
				<?php echo esc_html( $post_type_object->label ); ?></option>
			<?php
		}
		?>
	</select>
	<?php
}


/**
 *
 */
function display_bulk_data_push_button() {
	?>
	<button id="bulk_data_push">Push</button>
	<?php
}


/**
 *
 */
function display_error_log() {
	$error = new Error();
	?>
	<textarea class="error_log" style="height: 500px; width: 100%;"><?php echo esc_html( $error->get_log() ); ?></textarea>
	<?php
}


/**
 *
 */
function display_connected_sites() {
	// blogname, Site ID, URL, date connected, remove button, connect new.
	$connected_sites = get_option( 'connected_sites' );
	?>
	<table id="connected_sites">
		<thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>URL</th>
			<th>Date Connected</th>
			<th>Remove</th>
		</tr>
		</thead>

		<tbody>
		<?php
		if ( is_array( $connected_sites ) ) {
			$i=0;
			foreach ( $connected_sites as $site ) {
				?>
				<tr>
					<td id="id"><?php echo esc_html( $i ); ?></td>
					<td id="name"><?php echo esc_html( $site['name'] ); ?></td>
					<td id="url"><?php echo esc_html( $site['url'] ); ?></td>
					<td id="date_connected"><?php echo esc_html( $site['date_connected'] ); ?></td>
					<td id="site-<?php echo esc_html( $i ); ?>"><span class="dashicons dashicons-trash remove_site"></span></td>
				</tr>
				<?php
				$i++;
			}
		}
		?>
		<tr>
			<td>
				<button id="add_site">Add Site</button>
			</td>
		</tr>
		</tbody>
	</table>
	<input type="hidden" name="connected_sites[]" value="<?php echo esc_html( $connected_sites ); ?>"/>
	<?php

	display_modal();
}

/**
 *
 */
function display_modal() {
	?>
	<div class="lightbox_wrap">
		<div class="add_site_modal">
			<a id="close">X</a>
			<h2>Add New Site</h2>
			<form>
				<div class="input_wrap">
					<label for="name">Site Name</label>
					<input type="text" name="name" value="" id="site_name"/>
				</div>

				<div class="input_wrap">
					<label for="url">Site URL</label>
					<input type="text" name="url" value="" id="site_url"/>
				</div>

				<p class="submit"><input type="submit" name="submit_site" id="submit_site" class="button button-primary" value="Add Site"></p>
			</form>
		</div>
	</div>
	<?php
}

/**
 *
 */
function display_notified_users() {
	$users_query = new WP_User_Query(
		array(
			// 'role' => 'administrator',
				'orderby' => 'display_name',
		)
	);  // query to get admin users

	$users = $users_query->get_results();
	?>
	<select name="notified_users[]" multiple>
	<?php

	foreach ( $users as $user ) {
		?>
		<option
		value="<?php echo $user->id; ?>" <?php selected( in_array( $user->id, get_option( 'notified_users' ) ) ); ?>><?php echo $user->user_nicename; ?></option>
						  <?php
	}
	?>
	</select>
	<?php
}


/**
 *
 */
function display_post_types_to_accept() {
	$args = array(
		'public' => true,
	// '_builtin' => false
	);
	$output   = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$registered_post_types = get_post_types( $args, $output, $operator );
	?>
	<select name="enabled_post_types[]" multiple id="enabled_post_types">
	<?php

	foreach ( $registered_post_types as $key => $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		?>
		<option
		value="<?php echo $post_type_object->name; ?>" <?php selected( in_array( $post_type_object->name, get_option( 'enabled_post_types' ) ) ); ?>><?php echo $post_type_object->label; ?></option>
						  <?php
	}
	?>
	</select>
	<?php
}


/**
 * @param $post_type_object
 */
function display_post_type_permissions_settings( $post_type_object ) {
	$post_type_object = $post_type_object[0];
	?>
	<select name="<?php echo $post_type_object->name . '_perms[]'; ?>" multiple>
		<option
			value="create_posts" <?php selected( in_array( 'create_posts', get_option( $post_type_object->name . '_perms' ) ) ); ?>>
			Create Posts<br>
		<option
			value="create_terms" <?php selected( in_array( 'create_terms', get_option( $post_type_object->name . '_perms' ) ) ); ?>>
			Create Terms<br>
		<option
			value="edit_content" <?php selected( in_array( 'edit_content', get_option( $post_type_object->name . '_perms' ) ) ); ?>>
			Edit Content<br>
		<option
			value="edit_status" <?php selected( in_array( 'edit_status', get_option( $post_type_object->name . '_perms' ) ) ); ?>>
			Edit Status & Visibility<br>
	</select>
	<?php
}


/**
 *
 */
function display_pull_data_button() {
	?>
	<button id="data_pull">Pull</button>
	<?php
}
