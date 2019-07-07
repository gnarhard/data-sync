<?php

namespace DataSync;

use DataSync\Controllers\Logs;

function display_log() {
	$logs = Logs::get_log();

	if ( count( $logs ) ) {

		?>
		<table>
			<thead>
			<tr>
				<td>TIME</td>
				<td>LOG ENTRY</td>
				<td>URL</td>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach( $logs as $log ) {
				$time        = strtotime( $log->datetime );
				$datetime      = date( 'g:i a F j, Y', $time );
				?>
				<tr>
					<td><?php echo $datetime ?></td>
					<td><?php echo $log->log_entry ?></td>
					<td><?php echo $log->url_source ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		
	} else {
		echo 'No log entries.';
	}
}