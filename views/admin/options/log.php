<?php

namespace DataSync;

use DataSync\Controllers\Logs;

function display_log() {
	$log = Logs::get_log();

	if ( count( $log ) ) {
		Logs::format( $log );
		echo $log[0];
	} else {
		echo 'No log entries.';
	}
}