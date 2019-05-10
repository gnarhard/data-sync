<?php namespace DataSync;
function data_sync_settings() {
 ?>
  <div class="wrap">
    <h2>Data Sync Settings</h2>
    <form method="POST" action="options.php">
      <?php
      settings_fields( 'data_sync_settings' );
      do_settings_sections( 'data-sync-settings' );
      submit_button();
      ?>
    </form>
  </div>
  <?php
}