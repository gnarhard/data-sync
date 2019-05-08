<?php

use WPDataSync\Auth as Auth;

function display_source_input() {
	$source = get_option( 'source_site' ); ?>

	<br>
	<input type="radio" name="source_site" id="source_site"
	       value="1" <?php checked( '1', get_option( 'source_site' ) ); ?>/> Source
	<br>
	<input type="radio" name="source_site" id="source_site" value="0" <?php checked( '0', get_option( 'source_site' ) );
	if ( $source === false ) {
		echo 'checked';
	}
	?>
	/> Receiver

	<?php
}




function display_push_template_button() {
	?><button id="push_template">Push</button><?php
}



function display_push_enabled_post_types() {
	$registered_post_types = get_post_types();

	?><select name="push_enabled_post_types[]" multiple style="width: 200px; height: 300px"><?php

	foreach ($registered_post_types as $key => $post_type) {
		?><option value="<?php echo $key?>" ><?php echo $post_type?></option><?php
	}

	?></select><?php

	var_dump(get_option('push_enabled_post_types'));
}


function display_bulk_data_push_button() {
	?><button id="bulk_data_push">Push</button><?php
}



function display_error_log() {
  $error_log = file_get_contents(WP_DATA_SYNDICATOR_PATH . 'error.log');
  ?><textarea class="error_log" style="height: 500px; width: 100%;"><?php echo $error_log?></textarea><?php
}



function display_token() {
	// UNIQUE SITE TOKEN (public key)
	if (get_option('public_key') === false) {
		$auth = new Auth();
		$publicKey = $auth->getPublicKey();
		?>
		<textarea name="public_key" id="public_key"><?php echo $publicKey;?></textarea>
		<?php
	} else {
		?>
		<textarea name="public_key" id="public_key"><?php echo get_option('public_key');?></textarea>
		<?php
	}
}

function display_notified_users() {

	$users_query = new WP_User_Query( array(
		'role' => 'administrator',
		'orderby' => 'display_name'
	) );  // query to get admin users

	$users = $users_query->get_results();
	$i=0;

	?><select name="notified_users[]" multiple style="width: 200px;"><?php

	foreach ($users as $user) {
      ?><option value="<?php echo $user->id?>" ><?php echo $user->user_nicename?></option><?php
  }

	?></select><?php
	var_dump(get_option('notified_users'));
}

//selected( get_option( 'notified_users' ), $user->id );


function display_post_types_to_accept() {

	$registered_post_types = get_post_types();

	?><select name="post_types_to_accept[]" multiple style="width: 200px;"><?php

	foreach ($registered_post_types as $key => $post_type) {
		?><option value="<?php echo $key?>" ><?php echo $post_type?></option><?php
	}

	?></select><?php

	var_dump(get_option('post_types_to_accept'));

}



function display_post_types_permissions() {

}




function display_pull_data_button() {
  ?><button id="data_pull">Pull</button><?php
}