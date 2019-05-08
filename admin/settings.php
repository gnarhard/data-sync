<?php

?>
<h1>WP Data Sync Settings</h1>
<form method="POST" action="options.php">
  <?php
  settings_fields( 'wp-data-sync-settings' );	//pass slug name of page, also referred to in Settings API as option group name
  do_settings_sections( 'wp-data-sync-settings' ); 	//pass slug name of page
  display_source_input();
  submit_button();
  ?>
</form>
