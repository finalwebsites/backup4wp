<?php
include_once 'func.php';


if (!empty($_POST['emailfrom']) && !empty($_POST['adminemail'])) {
    $emailfrom = filter_var($_POST['emailfrom'], FILTER_SANITIZE_EMAIL);
    $adminemail = filter_var($_POST['adminemail'], FILTER_SANITIZE_EMAIL);
	$sendgridapi = filter_var($_POST['sendgridapi'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$stmt = $db->prepare("REPLACE INTO backupsettings (id, sendgridapi, emailfrom, adminemail) VALUES (:id, :sendgridapi, :emailfrom, :adminemail)");
		$stmt->bindValue(':id', 1, SQLITE3_INTEGER);
		$stmt->bindValue(':sendgridapi', $sendgridapi, SQLITE3_TEXT);
		$stmt->bindValue(':emailfrom', $emailfrom, SQLITE3_TEXT);
		$stmt->bindValue(':adminemail', $adminemail, SQLITE3_TEXT);
		$stmt->execute();
		$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
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
    echo 'Error: The required fields are empty.';
}
