<?php

namespace DataSync;

use DataSync\Controllers\TemplateSync;

function display_synced_templates()
{
    ?>
	<ul>
		<?php
        $files = TemplateSync::get_template_files();
    foreach ($files as $file) {
        if (('.' === $file) || ('..' === $file) || ('index.php' === $file)) {
            continue;
        } ?>
			<li><?php echo $file ?></li><?php
    } ?>
	</ul>
	<button id="template_push" class="button button-primary"><?php _e('Push Templates', 'data_sync'); ?></button>
	<?php
}
