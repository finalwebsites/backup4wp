<?php
include_once 'func.php';

// TODO: exclude sql file for file restore

define('MYBACKUPDIR', dirname(dirname(__FILE__)).'/');
define('ABSPATH', dirname(MYBACKUPDIR).'/');

if (!empty($_POST['backupid'])) {
	try {
		$db = new PDO('sqlite:../data/wpbackupsDb_PDO.sqlite');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sth = $db->prepare("SELECT dirname, excludedata, backuptype, database FROM wpbackups WHERE id = :id");
		$sth->bindValue(':id', $_POST['backupid'], SQLITE3_INTEGER);
		$sth->execute();
		$result = $sth->fetch(PDO::FETCH_OBJ);
		$excl = unserialize($result->excludedata);
		$excl_str = ($result->backuptype == 'full') ? '--exclude mybackup' : '';
		if (count($excl) > 0) {
			foreach ($excl as $dir) {
				$pathpart = ($result->backuptype == 'part') ? $dir : 'wp-content/'.$dir;
				$excl_str .= ' --exclude '.$pathpart;
			}
		}
		$restore_targ = ($result->backuptype == 'part') ? ABSPATH.'wp-content/' : ABSPATH;
		$restore_src = ($result->backuptype == 'part') ? MYBACKUPDIR.'files/'.$result->dirname.'/wp-content/' : MYBACKUPDIR.'files/'.$result->dirname.'/';
		$restore = sprintf('rsync -a --delete-after %s %s %s 2>&1', $excl_str, $restore_src, $restore_targ);
		$restresp = shell_exec($restore);
		if ($restresp != '') die('An error occured while restoring the files.');

		if ($result->database == 1) {
			$conn = get_db_conn_vals(ABSPATH);
			if (isset($conn['DB_NAME'], $conn['DB_USER'], $conn['DB_PASSWORD'], $conn['DB_HOST'])) {
				$sqlpath = MYBACKUPDIR.'files/'.$result->dirname.'/database.sql';
				/*$mysql = sprintf('MYSQL_PWD=\'%s\' mysql --user=%s %s < %s 2>&1', $conn['DB_PASSWORD'], $conn['DB_USER'], $conn['DB_NAME'], $sqlpath);
				$dbiresp = shell_exec($mysql);
				if ($dbiresp == '' || substr($dbiresp, 0, 7) == 'Warning') {
					if (file_exists(ABSPATH.'database.sql')) unlink(ABSPATH.'database.sql');
				} else {
					if (file_exists(ABSPATH.'database.sql')) unlink(ABSPATH.'database.sql');
					die('DB error: '.$dbiresp);
				}*/
				$response = restore_database($conn['DB_HOST'], $conn['DB_USER'], $conn['DB_PASSWORD'], $conn['DB_NAME'], $sqlpath);
				if (false ==  filter_var($response, FILTER_VALIDATE_BOOLEAN)) {
					die($response);
				}
			}
		}
	} catch(PDOException $e) {
		die('Exception : '.$e->getMessage());
	}
} else {
	die('The backup ID is missing.');
}

echo 'okay';
