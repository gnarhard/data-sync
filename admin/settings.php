<?php
use WPDataSync\Auth as Auth;
require_once 'register.php';
?>
<h1>WP Data Sync Settings</h1>
<form method="POST" action="/wp-admin/options-general.php">
  <?php settings_fields( 'wp-data-sync-settings' );	//pass slug name of page, also referred to in Settings API as option group name
  do_settings_sections( 'wp-data-sync-settings' ); 	//pass slug name of page
  submit_button();
  ?>
</form>
