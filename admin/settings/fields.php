<?php namespace DataSync;

use WP_User_Query;

function display_source_input() {
	$source = get_option( 'source_site' );?>
	<input type="radio" name="source_site" id="source_site" value="1" <?php checked( '1', get_option( 'source_site' ) ); ?>/> Source
	<br>
	<input type="radio" name="source_site" id="source_site" value="0" <?php checked( '0', get_option( 'source_site' ) );?>/> Receiver

	<?php
}




function display_push_template_button() {
	?><button id="push_template">Push</button><?php
}



function display_push_enabled_post_types() {

  $args = array(
      'public'   => true,
//            '_builtin' => false
  );
  $output = 'names'; // names or objects, note names is the default
  $operator = 'and'; // 'and' or 'or'

  $registered_post_types = get_post_types($args, $output, $operator);

  ?><select name="push_enabled_post_types[]" multiple id="push_enabled_post_types"><?php

  foreach ($registered_post_types as $key => $post_type) {

    $post_type_object = get_post_type_object( $post_type );
    ?><option value="<?php echo $post_type_object->name?>" <?php selected( in_array($post_type_object->name, get_option( 'push_enabled_post_types' )) );?>><?php echo $post_type_object->label?></option><?php

  }

  ?></select><?php

}


function display_bulk_data_push_button() {
	?><button id="bulk_data_push">Push</button><?php
}



function display_error_log() {
  $error_log = file_get_contents(DATA_SYNC_PATH . 'error.log');
  ?><textarea class="error_log" style="height: 500px; width: 100%;"><?php echo $error_log?></textarea><?php
}



function display_connected_sites() {

  // blogname, Site ID, URL, date connected, remove button, connect new
  $connectedSites = get_option('connected_sites');
  ?>
  <table>
    <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>URL</th>
      <th>Date Connected</th>
      <th></th>
    </tr>
    </thead>

    <tbody>
      <?php
      if (is_array($connectedSites)) {
        foreach($connectedSites as $site) {

        }
      }
      ?>
      <tr><td><button id="add_site">Add Site</button></td></tr>
    </tbody>
  </table>
  <input type="hidden" name="connected_sites[]" value="<?php echo $connectedSites?>" />
  <?php

  display_modal();

}

function display_modal() {
  ?>
    <div class="lightbox_wrap">
      <div class="add_site_modal">
        <a id="close">X</a>
        <h2>Add New Site</h2>
        <form>
          <div class="input_wrap">
            <label for="name">Site Name</label>
            <input type="text" name="name" value="" id="name"/>
          </div>

          <div class="input_wrap">
            <label for="url">Site URL</label>
            <input type="text" name="url" value="" id="url"/>
          </div>

          <div class="input_wrap">
            <label for="token">Reciever Security Token</label>
            <textarea name="token" id="token"></textarea>
          </div>

          <p class="submit"><input type="submit" name="submit_site" id="submit_site" class="button button-primary" value="Add Site"></p>
        </form>
      </div>
    </div>
  <?php

}



function display_token_receiver() {

  ?><label for="security_token_receiver">Copy and paste this into your source site's security token field:</label><br><?php

	// UNIQUE SITE TOKEN (public key)
	if (get_option('security_token_receiver') === false) {
		$auth = new Auth();
		$auth->setKeys();
		$publicKey = $auth->getPublicKey();
		?>
		<textarea name="security_token_receiver" id="security_token_receiver"><?php echo $publicKey;?></textarea>
		<?php
	} else {
		?>
		<textarea name="security_token_receiver" id="public_key"><?php echo get_option('security_token_receiver');?></textarea>
		<?php
	}
}

function display_notified_users() {

	$users_query = new WP_User_Query( array(
//		'role' => 'administrator',
		'orderby' => 'display_name'
	) );  // query to get admin users

	$users = $users_query->get_results();

	?><select name="notified_users[]" multiple><?php

	foreach ($users as $user) {

      ?><option value="<?php echo $user->id?>" <?php selected( in_array($user->id, get_option( 'notified_users' )) );?>><?php echo $user->user_nicename?></option><?php
  }

	?></select><?php
}




function display_post_types_to_accept() {

	$args = array(
		'public'   => true,
//            '_builtin' => false
	);
	$output = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$registered_post_types = get_post_types($args, $output, $operator);

	?><select name="enabled_post_types[]" multiple id="enabled_post_types"><?php

	foreach ($registered_post_types as $key => $post_type) {

	  $post_type_object = get_post_type_object( $post_type );
		?><option value="<?php echo $post_type_object->name?>" <?php selected( in_array($post_type_object->name, get_option( 'enabled_post_types' )) );?>><?php echo $post_type_object->label?></option><?php

	}

	?></select><?php


}



function display_post_type_permissions_settings($post_type_object) {

	$post_type_object = $post_type_object[0];
  ?>
    <select name="<?php echo $post_type_object->name . '_perms[]'?>" multiple>
  <option value="create_posts" <?php selected( in_array('create_posts', get_option( $post_type_object->name . '_perms' )) ); ?>>Create Posts<br>
  <option value="create_terms" <?php selected( in_array('create_terms', get_option( $post_type_object->name . '_perms' )) ); ?>>Create Terms<br>
  <option value="edit_content" <?php selected( in_array('edit_content', get_option( $post_type_object->name . '_perms' )) ); ?>>Edit Content<br>
  <option value="edit_status" <?php selected( in_array('edit_status', get_option( $post_type_object->name . '_perms' )) ); ?>> Edit Status & Visibility<br>
    </select>
  <?php

}




function display_pull_data_button() {
  ?><button id="data_pull">Pull</button><?php
}