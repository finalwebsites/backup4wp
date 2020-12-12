<?php

include_once 'func.php';
// todo google recaptcha

if (!empty($_POST['mailto'])) {
	if (filter_var($_POST['mailto'], FILTER_VALIDATE_EMAIL)) {
		$email = $_POST['mailto'];
		$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
		$stmt = $db->prepare("SELECT adminemail FROM backupsettings WHERE adminemail = :adminemail");
		$stmt->bindValue(':adminemail', $email, SQLITE3_TEXT);
		if ($stmt->execute()) {
			$url = create_login_url();
			if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
				echo 'Error: '.$url;
			} else {
				$message = email_template('Click the link bebow to access the Backup4WP tool for your WordPress website.', $url);
				$subject = 'Your Backup4WP access link';
				$response = sendemail( $email, $subject, $message, 'Message sent successfully. Check your inbox for the magic link.' );
				echo $response;
			}
		} else {
			echo 'Error: '.$db->lastErrorMsg;
		}
		$db->close;
	}
}
