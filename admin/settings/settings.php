<?php
function wp_data_sync_settings() {
 ?>
  <div class="wrap">
    <h2>WP Data Sync Settings</h2>
    <form method="POST" action="options.php">
      <?php
      settings_fields( 'wp_data_sync_global_settings' );
      settings_fields( 'wp_data_sync_source_settings' );
      settings_fields( 'wp_data_sync_receiver_settings' );
      do_settings_sections( 'wp-data-sync-settings' );
      submit_button();
      ?>
    </form>
  </div>
  <?php
}