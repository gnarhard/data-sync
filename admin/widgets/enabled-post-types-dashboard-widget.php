<?php namespace DataSync;

/**
 * Dashboard widget that displays which post types are enabled to be synced
 */
function enabled_post_types_widget() {
	display_push_enabled_post_types(); ?>
	<button id="save_push_enabled_post_types">Save</button>
	<?php
}
