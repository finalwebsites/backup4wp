<?php
include_once 'func.php';


if (!empty($_POST['emailfrom']) && !empty($_POST['adminemail'])) {
    $emailfrom = filter_var($_POST['emailfrom'], FILTER_SANITIZE_EMAIL);
    $adminemail = filter_var($_POST['adminemail'], FILTER_SANITIZE_EMAIL);
	$sendgridapi = filter_var($_POST['sendgridapi'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	$smtpserver = filter_var($_POST['smtpserver'], FILTER_SANITIZE_URL);
	$smtpport = intval($_POST['smtpport']);
	$smtplogin = filter_var($_POST['smtplogin'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	$smtppassword = filter_var($_POST['smtppassword'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	$smtpsecure = filter_var($_POST['smtpsecure'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	


	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		
		$row = $db->querySingle("SELECT adminemail, confirmed FROM backupsettings WHERE id = 1", true);
		if ($row['adminemail'] != $adminemail) {
			$confirmed = 'no';
		} else {
			$confirmed = $row['confirmed'];
		}
		
		$stmt = $db->prepare("UPDATE backupsettings SET sendgridapi = :sendgridapi, smtpserver = :smtpserver, smtpport = :smtpport, smtplogin = :smtplogin, smtppassword = :smtppassword, smtpsecure = :smtpsecure, emailfrom = :emailfrom, adminemail = :adminemail, confirmed = :confirmed, lastupdate = :lastupdate WHERE id = 1");
		$stmt->bindValue(':sendgridapi', $sendgridapi, SQLITE3_TEXT);
		$stmt->bindValue(':smtpserver', $smtpserver, SQLITE3_TEXT);
		$stmt->bindValue(':smtpport', $smtpport, SQLITE3_INTEGER);
		$stmt->bindValue(':smtplogin', $smtplogin, SQLITE3_TEXT);
		$stmt->bindValue(':smtppassword', $smtppassword, SQLITE3_TEXT);
		$stmt->bindValue(':smtpsecure', $smtpsecure, SQLITE3_TEXT);
		$stmt->bindValue(':emailfrom', $emailfrom, SQLITE3_TEXT);
		$stmt->bindValue(':adminemail', $adminemail, SQLITE3_TEXT);
		$stmt->bindValue(':confirmed', $confirmed, SQLITE3_TEXT);
		$stmt->bindValue(':lastupdate', date('Y-m-d h:i:s'), SQLITE3_TEXT);

		$stmt->execute();
		
		if ($confirmed == 'yes') {
			echo 'okay';
		} else {
			$url = create_login_url();
			$message = email_template('Click the link below and confirm your email address for the MyBackup tool.', $url);
			$subject = 'Please confirm your MyBackup login';
			$response = sendemail( $adminemail, $subject, $message, 'Message sent successfully. Check your inbox and confirm you email address.' );
			echo $response;
		}
	} 
} else {
    echo 'Error: The required email (from) field is empty.';
}
