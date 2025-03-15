<?php
include_once 'func.php';

if( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) ) {

	if (!empty($_POST['emailfrom']) && !empty($_POST['adminemail'])) {
		$emailfrom = filter_var($_POST['emailfrom'], FILTER_SANITIZE_EMAIL);
		$adminemail = filter_var($_POST['adminemail'], FILTER_SANITIZE_EMAIL);
		$mailersendapi  = htmlspecialchars($_POST['mailersendapi']);
		$smtpserver = filter_var($_POST['smtpserver'], FILTER_SANITIZE_URL);
		$smtpport = intval($_POST['smtpport']);
		$smtplogin = htmlspecialchars($_POST['smtplogin']);
		$smtppassword = htmlspecialchars($_POST['smtppassword']);
		$smtpsecure = htmlspecialchars($_POST['smtpsecure']);
		$emailtype = htmlspecialchars($_POST['emailtype']);
		$valid = true;
		switch ($emailtype) {
			case 'mailersend':
			if ($mailersendapi == '') {
				echo 'Enter a valid API key'.
				$valid = false;
			}
			break;
			case 'smtp':
			if ($smtpserver == '' || $smtpport < 25 || $smtplogin == '' || $smtppassword == '') {
				echo 'All fields for the SMTP configuration are required.';
				$valid = false;
			}
			break;
			default;
			break;
		}

		$apikey = $mailersendapi;
		//var_dump($apikey);

		if ($valid) {
            
			if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {

				$row = $db->querySingle("SELECT adminemail, confirmed FROM backupsettings WHERE id = 1", true);
				if ($row['adminemail'] != $adminemail) {
					$confirmed = 'no';
				} else {
					$confirmed = $row['confirmed'];
				}


				$stmt = $db->prepare("UPDATE backupsettings SET apikey = :apikey, smtpserver = :smtpserver, smtpport = :smtpport, smtplogin = :smtplogin, smtppassword = :smtppassword, smtpsecure = :smtpsecure, emailfrom = :emailfrom, adminemail = :adminemail, confirmed = :confirmed, emailtype = :emailtype, lastupdate = :lastupdate WHERE id = 1");
				$stmt->bindValue(':apikey', $apikey, SQLITE3_TEXT);
				$stmt->bindValue(':smtpserver', $smtpserver, SQLITE3_TEXT);
				$stmt->bindValue(':smtpport', $smtpport, SQLITE3_INTEGER);
				$stmt->bindValue(':smtplogin', $smtplogin, SQLITE3_TEXT);
				$stmt->bindValue(':smtppassword', $smtppassword, SQLITE3_TEXT);
				$stmt->bindValue(':smtpsecure', $smtpsecure, SQLITE3_TEXT);
				$stmt->bindValue(':emailfrom', $emailfrom, SQLITE3_TEXT);
				$stmt->bindValue(':adminemail', $adminemail, SQLITE3_TEXT);
				$stmt->bindValue(':confirmed', $confirmed, SQLITE3_TEXT);
				$stmt->bindValue(':emailtype', $emailtype, SQLITE3_TEXT);
				$stmt->bindValue(':lastupdate', date('Y-m-d h:i:s'), SQLITE3_TEXT);

				$stmt->execute();

				if ($confirmed == 'yes') {
					echo 'okay';
				} else {
					$url = create_login_url();
					$message = email_template('Click the link below and confirm your email address for the Backup4WP tool.', $url);
					$subject = 'Please confirm your Backup4WP login';
					$response = sendemail( $adminemail, $subject, $message, 'Message sent successfully. Check your inbox and confirm you email address.' );
					if ($response['status'] == 'error') {
						delete_login_record();
					}
					echo $response['msg'];
				}
			} else {
                echo 'DB error';
            }
		}
	} else {
		echo 'Error: The required email (from) field is empty.';
	}
}
