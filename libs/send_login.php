<?php

include_once 'func.php';

if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) ) {

	if (!empty($_POST['mailto'])) {
		if (filter_var($_POST['mailto'], FILTER_VALIDATE_EMAIL)) {
			$email = $_POST['mailto'];
			$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
			$stmt = $db->prepare("SELECT adminemail FROM backupsettings WHERE adminemail = :adminemail");
			$stmt->bindValue(':adminemail', $email, SQLITE3_TEXT);
			$res = $stmt->execute();
			$result = $res->fetchArray();
			$db->close();
			if (isset($result['adminemail']) && $result['adminemail'] == $email) {
				$url = create_login_url();

				if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
					echo 'Error: '.$url;
				} else {
					$message = email_template('Click the link belbow to access the Backup4WP tool for your WordPress website.', $url);
					$subject = 'Your Backup4WP access link';
					$response = sendemail( $email, $subject, $message, 'Message sent successfully. Check your inbox for the magic link.' );
					echo $response['msg'];
				}
			} else {
				echo 'Error: '.$email.' is not the registered email address.';
			}
			
		}
	}
}
