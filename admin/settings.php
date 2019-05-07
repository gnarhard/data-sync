<?php
?>
<h1>WP Data Sync Settings</h1>

<!--
SOURCE OR RECEIVER?
SOURCE = 1
-->
<?php $source = get_option( 'source_site' );?>

	<label for="type">Source or Receiver?</label>
	<br>
	<input type="radio" name="source_site" id="source_site" value="1" <?php checked( '1', get_option( 'source_site' ) ); ?>/> Source
	<br>
	<input type="radio" name="source_site" id="source_site" value="0" <?php checked( '0', get_option( 'source_site' ) ); if ($source === false) { echo 'checked'; } ?>/> Receiver

<?php

if ($source == 1) {

	/////// SOURCE OPTIONS ///////
// Connected Sites
// Synced Post Types
// Which is canonical per post?
// Re-sync button
// See error log

} else {

	/////// RECEIVER OPTIONS ///////

}
?>




<?php
//function display_theme_panel_fields()
//{
//
//	echo 'asdf 1 3';
//	add_settings_section("asdf", "All Settings", null, "wp-data-sync-settings");
//
//	add_settings_field("twitter_url", "Twitter Profile Url", "display_twitter_element", "wp-data-sync-settings", "asdf");
//	add_settings_field("facebook_url", "Facebook Profile Url", "display_facebook_element", "wp-data-sync-settings", "asdf");
//
//	register_setting("wp-data-sync-settings", "twitter_url");
//	register_setting("wp-data-sync-settings", "facebook_url");
//}

//add_action("admin_init", "display_theme_panel_fields");