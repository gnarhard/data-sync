<?php

namespace DataSync;

use DataSync\Controllers\Logs;

function display_log()
{
    $logs   = Logs::get_log();
    $output = '';

    if (count($logs)) {
        $output .= '<table>';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<td>LOG ENTRY</td>';
        $output .= '<td>URL</td>';
        $output .= '<td>TIME</td>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        foreach ($logs as $log) {
            $time     = strtotime($log->datetime);
            $datetime = date('g:i a F j, Y', $time);

            $output .= '<tr>';
            $output .= '<td>' . $log->log_entry . '</td>';
            $output .= '<td>' . $log->url_source . '</td>';
            $output .= '<td>' . $datetime . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
    } else {
        return 'No log entries.';
    }

    return $output;
}
