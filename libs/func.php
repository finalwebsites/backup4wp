<?php

define('MYBACKUPDIR', dirname(dirname(__FILE__)).'/');
define('ABSPATH', dirname(MYBACKUPDIR).'/');
define('DATAPATH', dirname(dirname(MYBACKUPDIR)).'/backups/');
define('BASE_URL', '//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/');

ini_set('max_execution_time', '120');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require MYBACKUPDIR . 'libs/phpmailer/Exception.php';
require MYBACKUPDIR . 'libs/phpmailer/PHPMailer.php';
require MYBACKUPDIR . 'libs/phpmailer/SMTP.php';

require_once MYBACKUPDIR . 'libs/sendgrid/sendgrid-php.php';

// This should be the part of the install process
if (!file_exists(DATAPATH)) {

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
				'sendgridapi' TEXT,
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
			INSERT INTO backupsettings (id, sendgridapi, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, emailfrom, adminemail, confirmed, emailtype, lastupdate)
			VALUES (1, '', '', 587, '', '', 'tls', '', '', 'no', 'sendgrid', '')"
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
}

function check_cookie() {
	if (check_htaccess()) {
		return true;
	}elseif (empty($_COOKIE['mybackup_access'])) {
		return false;
	} else {
		if (preg_match('/^[a-f0-9]{32}$/i', $_COOKIE['mybackup_access'], $matches)) {
			$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
			$stmt = $db->prepare("SELECT ipadres FROM logins WHERE slug = :slug ORDER BY created DESC");
			$stmt->bindValue(':slug', $matches[0], SQLITE3_TEXT);
			$res = $stmt->execute();
			if ($result = $res->fetchArray()) {
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
	$home .= '://'.$_SERVER['HTTP_HOST'].'/mybackup/';
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
		if ($confirmed != 'yes' && empty($_GET['auth'])) {
			if ($_SERVER['REQUEST_URI'] != '/mybackup/options.php') {
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
					header('Location: '.$home.'login.php?msg=expiredlink');
					exit;
				} else {
					if ($result['ipadres'] != get_client_ip()) {
						header('Location: '.$home.'login.php?msg=invalidsession');
						exit;
					} else {
						setcookie("mybackup_access", $matches[0], time()+(3600*4), "/mybackup/", $_SERVER['HTTP_HOST']);
						$confirmed = $db->querySingle("SELECT confirmed FROM backupsettings WHERE id = 1");
						if ($confirmed == 'no') {
							$db->exec("UPDATE backupsettings SET confirmed = 'yes' WHERE id = 1");
							header('Location: '.$home.'?msg=confirmed');
							exit;
						} else {
							header('Location: '.$home);
							exit;
						}
					}
				}
			} else {
				header('Location: '.$home.'login.php?msg=notfound');
				exit;
			}
		} else {
			if ($cookie = check_cookie()) {
				setcookie("mybackup_access", $cookie, time()+(3600*4), "/mybackup/", $_SERVER['HTTP_HOST']);
			} else {
				header('Location: '.$home.'login.php?msg=cookieexpired');
				exit;
			}
		}
	}

}

function create_login_url() {
	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	$url .= '://'.$_SERVER['HTTP_HOST'].'/mybackup/?auth=';
	$slug = md5(uniqid(rand(10000,99999), true));
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$stmt = $db->prepare("INSERT INTO logins (slug, created, ipadres) VALUES (:slug, :created, :ipadres)");
		$stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
		$stmt->bindValue(':created', time(), SQLITE3_INTEGER);
		$stmt->bindValue(':ipadres', get_client_ip(), SQLITE3_TEXT);
		if ($stmt->execute()) {
			$db->close();
			return $url.$slug;
		}
	}
}


function sendemail( $to, $subject, $msg, $return_msg = 'Message sent successfully.' ) {

	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$result = $db->querySingle("SELECT sendgridapi, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, emailfrom, emailtype FROM backupsettings WHERE id = 1", true);
		if ($result['emailtype'] == 'sendgrid') {

			$email = new \SendGrid\Mail\Mail();
			$email->setFrom($result['emailfrom'], 'MyBackup for WordPress');
			$email->setSubject($subject);
			$email->addTo($to);
			$email->addContent("text/plain", strip_tags($msg));
			$email->addContent("text/html", $msg);
			$sendgrid = new \SendGrid($result['sendgridapi']);
			try {
				$response = $sendgrid->send($email);
				//print_r($response);
				if ( in_array( $response->statusCode(), range(200, 299) ) ) {
					return $return_msg;
				} else {
					return 'Error, the message hasn\'t been sent.';
				}
			} catch (Exception $e) {
				return 'Caught exception: '. $e->getMessage() ."\n";
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
				$mail->setFrom($result['emailfrom'], 'MyBackup for WordPress');
				$mail->addAddress($to);
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body = $msg;
				$mail->AltBody = strip_tags($msg);
				$mail->send();
				return $return_msg;
			} catch (Exception $e) {
				return 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
			}
		} else {
			$headers = array(
				'From: '.$result->emailfrom,
				'X-Mailer: PHP/' . phpversion(),
				'MIME-Version: 1.0',
				'Content-type: text/html; charset=utf-8'
			);
			if (mail($to, $subject, $msg, implode("\r\n", $headers))) {
				return $return_msg;
			} else {
				return 'Error, the message hasn\'t been send via the PHP mail() function. Use the SMTP or Sendgrid option instead.';
			}
		}
	}
}

function get_db_conn_vals($dir) {
	$wp_config = $dir.'wp-config.php';
	$conn = array();
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
	return $conn;
}

function restore_database($host, $username, $password, $dbname, $sql_path){
    $db = new mysqli($host, $username, $password, $dbname);
    $templine = '';
    $lines = file($sql_path);
    $error = '';
    foreach ($lines as $line){
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
	$db->close();
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
