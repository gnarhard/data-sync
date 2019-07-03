<?php


namespace DataSync\Controllers;


class Email {

	public function __construct() {
		// TODO: email users after sync is complete.
		// TODO: LINK Log() TO EMAIL FOR EASIER STATUS UPDATES

		$connected_sites_obj = new ConnectedSites();
		$connected_sites     = $connected_sites_obj->get_all()->data;

		foreach ( get_option( 'notified_users' ) as $user_id ) {

			$user = get_user_by( 'ID', (int) $user_id );

			$headers  = 'From: ' . get_option( 'admin_email' ) . "\r\n";
			$headers .= 'Reply-To: ' . get_option( 'admin_email' ) . "\r\n";
			$headers .= 'X-Mailer: PHP/' . phpversion();
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

			$to      = $user->email;
			$subject = 'Data Sync Completed';

			$message  = 'Hello, ' . $user->first_name;
			$message .= '<br><br>';
			$message .= 'A data sync has completed between these sites:';
			$message .= '<br><br>';
			$message .= '<table id="connected_sites">';
			$message .= '<thead>
				            <tr>
						      <th>ID</th>
						      <th>Name</th>
						      <th>URL</th>
						    </tr>
					    </thead>';

			$message .= '<tbody>';

			foreach ( $connected_sites as $site ) {
				$message .= '<tr id="site-' . esc_html( $site->id ) . '">';
				$message .= '<td id="id">' . esc_html( $site->id ) . '</td>';
				$message .= '<td id="name">' . esc_html( $site->name ) . '</td>';
				$message .= '<td id="url">' . esc_html( $site->url ) . '</td>';
				$message .= '</tr>';
			}

			$message .= '</tbody>';
			$message .= '</table>';

			$sent = wp_mail( $to, $subject, $message, $headers );

			if ( $sent ) {
				new Log( 'Finished emailing notified users.' );
			} else {
				new Log( 'Email not sent.', true );
			}
		}

	}

}