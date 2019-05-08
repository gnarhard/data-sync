<?php

use WPDataSync\Auth as Auth;

function display_source_input() {
	$source = get_option( 'source_site' ); ?>

	<br>
	<input type="radio" name="source_site" id="source_site"
	       value="1" <?php checked( '1', get_option( 'source_site' ) ); ?>/> Source
	<br>
	<input type="radio" name="source_site" id="source_site" value="0" <?php checked( '0', get_option( 'source_site' ) );
//	if ( $source === false ) {
//		echo 'checked';
//	}
	?>
	/> Receiver

	<?php
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

/////// SOURCE OPTIONS ///////
// Connected Site blogname, Site ID, URL, date connected
// Synced Post Types
// Bulk push button
// See error log
    // Push new template file - cpt-templates.php


	////// RECEIVER OPTIONS /////
/// Users to be notified of emails
/// Which post types to accept
/// Pull data from source