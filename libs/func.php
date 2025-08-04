<?php

define('MYBACKUPDIR', dirname(dirname(__FILE__)).'/');
require_once MYBACKUPDIR.'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3));
$dotenv->safeLoad();


define('ABSPATH', dirname(MYBACKUPDIR).'/');
define('DATAPATH', dirname(dirname(MYBACKUPDIR)).'/backups/');
define('MBDIRNAME', '/'.basename(MYBACKUPDIR)); // for example /mybackup
define('BASE_URL', '//'.$_SERVER['HTTP_HOST'].MBDIRNAME.'/');

define('ENABLE_DOWNLOADS', false); // set to "true" to enable backup downnloads

define('BWP_VERSION', '1.3.2');

ini_set('max_execution_time', '120');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;

include_once 'MailerooClient.php';
use Maileroo\MailerooClient;

// This should be the part of the install process
if (!file_exists(DATAPATH.'wpbackupsDb.sqlite')) {

	mkdir(DATAPATH, 0755, true);

	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$db->exec("
			CREATE TABLE IF NOT EXISTS wpbackups (
				'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
				'dirname' TEXT,
				'dirsize' INTEGER,
				'insertdate' INTEGER,
				'excludedata' TEXT,
				'backuptype' TEXT,
				'database' INTEGER,
				'description' TEXT
			)"
		);
		$db->exec("
			CREATE TABLE IF NOT EXISTS backupsettings (
				'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
				'apikey' TEXT,
				'smtpserver' TEXT,
				'smtpport' INTEGER,
				'smtplogin' TEXT,
				'smtppassword' TEXT,
				'smtpsecure', TEXT,
				'emailfrom' TEXT,
				'adminemail' TEXT,
				'confirmed' TEXT,
				'emailtype' TEXT,
				'lastupdate' TEXT
			)"
		);

		$db->exec("
			INSERT INTO backupsettings (id, apikey, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, emailfrom, adminemail, confirmed, emailtype, lastupdate)
			VALUES (1, '', '', 587, '', '', 'tls', '', '', 'no', 'mailersend', '')"
		);

		$db->exec("
			CREATE TABLE IF NOT EXISTS logins (
				'slug' TEXT PRIMARY KEY NOT NULL,
				'created' TEXT,
				'ipadres' TEXT
			)"
		);
		$db->close();
	}
}

/*
// we need to check this later again
function update_mybackup() {
	$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
	$test = $db->querySingle("SELECT * FROM backupsettings WHERE id = 1", true);
	if (count($test) == 5) {
		$db->exec("ALTER TABLE backupsettings ADD COLUMN smtpserver TEXT");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN smtpport INTEGER");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN smtplogin TEXT");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN smtppassword TEXT");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN smtpsecure TEXT");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN emailtype TEXT");
		$db->exec("ALTER TABLE backupsettings ADD COLUMN lastupdate TEXT");
		$stmt = $db->prepare("UPDATE backupsettings SET smtpport = :smtpport, smtpsecure = :smtpsecure, emailtype = :emailtype, lastupdate = :lastupdate WHERE id = 1");
		$stmt->bindValue(':smtpport', $smtpport, SQLITE3_INTEGER);
		$stmt->bindValue(':smtpsecure', $smtpsecure, SQLITE3_TEXT);
		$stmt->bindValue(':emailtype', $emailtype, SQLITE3_TEXT);
		$stmt->bindValue(':lastupdate', date('Y-m-d h:i:s'), SQLITE3_TEXT);
		$stmt->execute();
	}
	$db->close();
}
*/
function check_cookie() {
	if (check_htaccess()) {
		return true;
	}elseif (empty($_COOKIE['backup4wp_access'])) {
		return false;
	} else {
		if (preg_match('/^[a-f0-9]{32}$/i', $_COOKIE['backup4wp_access'], $matches)) {
			//print_r($matches);
			$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
			$stmt = $db->prepare("SELECT ipadres FROM logins WHERE slug = :slug ORDER BY created DESC");
			$stmt->bindValue(':slug', $matches[0], SQLITE3_TEXT);
			$res = $stmt->execute();
			if ($result = $res->fetchArray()) {
				$db->close();
				if ($result['ipadres'] == get_client_ip()) {
					return $matches[0];
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

function check_htaccess() {
	$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
	$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
	$db->close();
	if ($confirmed == 'yes') return false;
	$file = MYBACKUPDIR.'.htaccess';
	if (file_exists($file)) {
		$f = fopen($file, 'r');
		$line = trim(fgets($f));
		fclose($f);
		if ($line == 'order deny,allow') {
			return true;
		} elseif ($line == 'AuthGroupFile /dev/null') {
			if (file_exists(MYBACKUPDIR.'.htpasswd')) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}


function get_authorized() {
	if (check_htaccess()) return;
	$home = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	$home .= '://'.$_SERVER['HTTP_HOST'].MBDIRNAME.'/';
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
		if ($confirmed != 'yes' && empty($_GET['auth'])) {
			if ($_SERVER['REQUEST_URI'] != MBDIRNAME.'/options.php') {
				$db->close();
				header('Location: '.$home.'options.php');
				exit;
			}

		} elseif (isset($_GET['auth']) && preg_match('/^[a-f0-9]{32}$/i', $_GET['auth'], $matches)) {
			$slug = $matches[0];
			$stmt = $db->prepare("SELECT created, ipadres FROM logins WHERE slug = :slug ORDER BY created DESC");
			$stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
			$res = $stmt->execute();
			if ($result = $res->fetchArray()) {
				if ($result['created']+(3600*4) < time()) {
					$db->close();
					header('Location: '.$home.'login.php?msg=expiredlink');
					exit;
				} else {
					if ($result['ipadres'] != get_client_ip()) {
						$db->close();
						header('Location: '.$home.'login.php?msg=invalidsession');
						exit;
					} else {
						setcookie("backup4wp_access", $matches[0], time()+(3600*4), "/", $_SERVER['HTTP_HOST']);
						$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
						if ($confirmed == 'no') {
							$db->exec("UPDATE backupsettings SET confirmed = 'yes' WHERE id = 1");
							$db->close();
							header('Location: '.$home.'?msg=confirmed');
							exit;
						} else {
							header('Location: '.$home);
							exit;
						}
					}
				}
			} else {
				$db->close();
				header('Location: '.$home.'login.php?msg=notfound');
				exit;
			}
		} else {
			if (check_cookie()) {
				setcookie("backup4wp_access", $slug, time()+(3600*4), "/", $_SERVER['HTTP_HOST']);
			} else {
				header('Location: '.$home.'login.php?msg=cookieexpired');
				exit;
			}
		}
	}

}

function create_login_url() {

	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	$url .= '://'.$_SERVER['HTTP_HOST'].MBDIRNAME.'/?auth=';
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$stmt = $db->prepare("SELECT slug, created FROM logins WHERE ipadres = :ipadres ORDER BY created DESC LIMIT 0, 1");
		$stmt->bindValue(':adminemail', get_client_ip(), SQLITE3_TEXT);
		$res = $stmt->execute();
		$result = $res->fetchArray();
		if (isset($result['slug']) && $result['created']+(3600*4) > time()) {
			$db->close();
			return $url.$result['slug'];
		} else {
			$slug = md5(uniqid(rand(10000,99999), true));
			$stmt = $db->prepare("INSERT INTO logins (slug, created, ipadres) VALUES (:slug, :created, :ipadres)");
			$stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
			$stmt->bindValue(':created', time(), SQLITE3_INTEGER);
			$stmt->bindValue(':ipadres', get_client_ip(), SQLITE3_TEXT);
			if ($stmt->execute()) {
				$return = $url.$slug;
			} else {
				$return = $db->lastErrorMsg();

			}
			$db->close();
			return $return;
		}
	}
}

function delete_login_record() {
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$stmt = $db->prepare("DELETE FROM logins WHERE ipadres = :ipadres");
		$stmt->bindValue(':adminemail', get_client_ip(), SQLITE3_TEXT);
		$res = $stmt->execute();
		$db->close();
	}
}


function sendemail( $to, $subject, $msg, $return_msg = 'Message sent successfully.' ) {

	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$result = $db->querySingle("SELECT apikey, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, emailfrom, emailtype FROM backupsettings WHERE id = 1", true);
		$db->close();
		$status = 'succes';
		$message = '';
		if ($result['emailtype'] == 'maileroo') {
			try {
				$client = new MailerooClient($result['apikey']);
				$client->setFrom($_SERVER['HTTP_HOST'], $result['emailfrom']);
				$client->setTo('WP Admin', $to);
				$client->setSubject($subject);
				$client->setHtml($msg);
				$client->setPlain(strip_tags($msg));
				$response = $client->sendBasicEmail();
                print_r($response);
                /*if ( $response['status_code'] == 202 ) {
					$message = $return_msg;
				} else {
                    $status = 'error';
					$message = 'Error, the message hasn\'t been sent.';
                }*/
			} catch (Exception $e) {
				$status = 'error';
				$message = 'Caught exception: ' . $e->getMessage() . "\n";
			}
		} elseif ($result['emailtype'] == 'mailersend') {
			$mailersend = new MailerSend(['api_key' => $result['apikey']]);
			$recipients = [
			    new Recipient($to, ''),
			]; 
			$emailParams = (new EmailParams())
			    ->setFrom($result['emailfrom'])
			    ->setFromName($_SERVER['HTTP_HOST'])
			    ->setRecipients($recipients)
			    ->setSubject($subject)
			    ->setHtml($msg)
			    ->setText(strip_tags($msg));
			try {
				$response = $mailersend->email->send($emailParams);
                if ( $response['status_code'] == 202 ) {
					$message = $return_msg;
				} else {
                    $status = 'error';
					$message = 'Error, the message hasn\'t been sent.';
                }
			} catch (\Exception $e) {
				$status = 'error';
				$message = 'Caught exception: ' . $e->getMessage() . "\n";
			}
		} elseif ($result['emailtype'] == 'smtp') {

			$mail = new PHPMailer(true);
			try {
				$mail->isSMTP();
				$mail->Host = $result['smtpserver'];
				$mail->SMTPAuth = true;
				$mail->Username = $result['smtplogin'];
				$mail->Password = $result['smtppassword'];
				$mail->SMTPSecure = $result['smtpsecure'];
				$mail->Port = $result['smtpport'];
				$mail->setFrom($result['emailfrom'], $_SERVER['HTTP_HOST']);
				$mail->addAddress($to);
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body = $msg;
				$mail->AltBody = strip_tags($msg);
				$mail->send();
				$message = $return_msg;
			} catch (Exception $e) {
				$status = 'error';
				$message = 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
			}
		} else {
			$headers = array(
				'From: '.$result->emailfrom,
				'X-Mailer: PHP/' . phpversion(),
				'MIME-Version: 1.0',
				'Content-type: text/html; charset=utf-8'
			);
			if (mail($to, $subject, $msg, implode("\r\n", $headers))) {
				$message = $return_msg;
			} else {
				$status = 'error';
				$message = 'Error, the message hasn\'t been send via the PHP mail() function. Use the SMTP or Sendgrid option instead.';
			}
		}
		return array('status' => $status, 'msg' => $message);
	}
}

function get_db_conn_vals($dir) {
	$conn = array();
	if (!empty($_ENV['DB_NAME']) && !empty($_ENV['DB_USER']) && !empty($_ENV['DB_PASSWORD']) && !empty($_ENV['DB_HOST'])) {
		$conn['DB_NAME'] = $_ENV['DB_NAME'];
		$conn['DB_USER'] = $_ENV['DB_USER'];
		$conn['DB_PASSWORD'] = $_ENV['DB_PASSWORD'];
		$conn['DB_HOST'] = $_ENV['DB_HOST'];
		$conn['DB_PREFIX'] = $_ENV['DB_PREFIX'];
	} else {
		$wp_config = $dir.'wp-config.php';
		if ( file_exists($wp_config) ) {
			if ($fc = fopen($wp_config, 'r') ) {
				while (! feof($fc)) {
					$line = fgets($fc);
					if ( preg_match('/^\s*define\s*\(\s*[\'"]DB_NAME[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
						$conn['DB_NAME'] = $match[1];
					} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_USER[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
						$conn['DB_USER'] = $match[1];
					} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*([\'"])(.+?)\1/', $line, $match) ) {
						$conn['DB_PASSWORD'] = $match[2];
					} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_HOST[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
						$conn['DB_HOST'] = $match[1];
					} elseif ( preg_match('/^\s*\$table_prefix\s*\=\s*[\'"]([a-zA-Z0-9_\-]*)[\'"]/', $line, $match) ) {
						$conn['DB_PREFIX'] = $match[1];
					}
				}
				fclose($fc);
			}
		}
	}
	return $conn;
}

function restore_database($host, $username, $password, $dbname, $sql_path){
    $db = new mysqli($host, $username, $password, $dbname);
    $templine = '';
    $error = '';
    $handle = fopen($sql_path, "r");
	if ($handle) {
		while (($line = fgets($handle)) !== false) {
        // Continue it if it's a comment empty row
			if(substr($line, 0, 2) == '--' || $line == ''){
				continue;
			}
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';'){
				if(!$db->query($templine)){
                $error .= 'Error performing "<b>' . $templine . '</b>": ' . $db->error . '<br />';
				}
				$templine = '';
			}
		}
		fclose($handle);
		$db->close();
	}
    return ($error != '') ? $error : true;
}

function get_client_ip() {
	foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
		if (array_key_exists($key, $_SERVER) === true){
			foreach (explode(',', $_SERVER[$key]) as $ip){
				$ip = trim($ip); // just to be safe

				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
					return $ip;
				}
			}
		}
	}
}

// Credits to Arseny Mogilev who posted this function to the PHP manual
function filesizeConvert($bytes) {
    $bytes = floatval($bytes);
	$arBytes = array(
		0 => array(
			'UNIT' => 'TB',
			'VALUE' => pow(1024, 4)
		),
		1 => array(
			'UNIT' => 'GB',
			'VALUE' => pow(1024, 3)
		),
		2 => array(
			'UNIT' => 'MB',
			'VALUE' => pow(1024, 2)
		),
		3 => array(
			'UNIT' => 'KB',
			'VALUE' => 1024
		),
		4 => array(
			'UNIT' => 'B',
			'VALUE' => 1
		),
	);
    foreach($arBytes as $arItem) {
        if($bytes >= $arItem['VALUE']) {
            $result = $bytes / $arItem['VALUE'];
            $result = str_replace('.', ',' , strval(round($result, 2))).' '.$arItem['UNIT'];
            break;
        }
    }
    return $result;
}

function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}

function email_template($info, $url) {
	return sprintf('
<html>
<head>
<style>
body {
	margin:0;
	padding:30px;
	text-align:center;
	font:14px Arial, sans-serif;
	line-height:2em;
	background-color:#efefef;
	color:#333333;
}
.mailcontainer {
	margin:20 auto;
	padding:20px;
	text-align:left;
	background-color:#ffffff;
	border:1px solid #BFBFBF
}
</style>
</head>
<body style="margin:0;padding:30px;text-align:center;font:14px Arial, sans-serif;line-height:2.0em;background-color:#efefef;">
<div class="mailcontainer" style="margin:auto;padding:20px;text-align:left;background-color:#ffffff;border:1px solid #BFBFBF">
<p>Hello Admin,<br>
%s</p>
<p><a href="%s">%s</a></p>
<p>Kind regards,<br>
Team Backup4WP</p>
</div>
</body>
</html>
', $info, $url, $url);
}
