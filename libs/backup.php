<?php
include_once 'func.php';

/** TODO **/
// Exlcude hidden files (maybe make the sql dump hidden too?)

define('MYBACKUPDIR', dirname(dirname(__FILE__)).'/');
define('ABSPATH', dirname(MYBACKUPDIR).'/');
$secret = 'f8dhfjdf7';

if (isset($_POST['Submitform'])) {
	$dirname = md5($secret.time());
	$partbackup = false;
	if ($_POST['typebackup'] == 'full') {
		$backup_src = ABSPATH;
		$backup_targ = MYBACKUPDIR.'files/'.$dirname;
	} else {
		$backup_src = ABSPATH.'wp-content/';
		$backup_targ = MYBACKUPDIR.'files/'.$dirname.'/wp-content';
		$partbackup = true;
	}
	mkdir($backup_targ, 0755, true);
	$excl_str = '';
	if (!empty($_POST['exclude'])) {
		$info .= 'Excl. ';
		foreach ($_POST['exclude'] as $excl) {
			$pathpart = ($partbackup) ? $excl : 'wp-content/'.$excl;
			$excl_str .= ' --exclude '.$pathpart;
		}
	}
	$database = 0;
	if (empty($_POST['excldb'])) {
		$conn = get_db_conn_vals(ABSPATH);

		if (isset($conn['DB_NAME'], $conn['DB_USER'], $conn['DB_PASSWORD'])) {
			$database = 1;
			exec(sprintf('mysqldump --user=%s --password=%s %s --result-file=%sfiles/%s/database.sql', $conn['DB_USER'], $conn['DB_PASSWORD'], $conn['DB_NAME'], MYBACKUPDIR, $dirname));
		}
	}
	$sync = sprintf('rsync -av --exclude mybackup%s %s %s', $excl_str, $backup_src, $backup_targ);
	exec($sync);
	$dirsize = dirSize(MYBACKUPDIR.'files/'.$dirname);
	try {
		//open the database
		$db = new PDO('sqlite:../data/wpbackupsDb_PDO.sqlite');
		$insert = "INSERT INTO wpbackups (dirname, dirsize, insertdate, excludedata, backuptype, database, description) VALUES (:dirname, :dirsize, :insertdate, :excludedata, :backuptype, :database, :description)";
		$stmt = $db->prepare($insert);
		$stmt->bindValue(':dirname', $dirname, SQLITE3_TEXT);
		$stmt->bindValue(':dirsize', $dirsize, SQLITE3_INTEGER);
		$stmt->bindValue(':insertdate', time(), SQLITE3_INTEGER);
		$stmt->bindValue(':excludedata', serialize($_POST['exclude']), SQLITE3_TEXT);
		$stmt->bindValue(':backuptype', $_POST['typebackup'], SQLITE3_TEXT);
		$stmt->bindValue(':database', $database, SQLITE3_INTEGER);
		$stmt->bindValue(':description', $_POST['description'], SQLITE3_TEXT);
		$stmt->execute();
	} catch(PDOException $e) {
		print 'Exception : '.$e->getMessage();
	}

	//$zip = sprintf('zip -r %sfiles/%s.zip %s -x \*mybackup\* \*wp-content/cache\* \*wp-content/uploads\*', MYBACKUPDIR, $name, ABSPATH);
	//$unzip = sprintf('unzip %sfiles/%s.zip %s\* %s/test', MYBACKUPDIR, $name, ABSPATH, dirname(ABSPATH));


	//$zip = sprintf('cd ../ && zip -ru %sfiles/%s.zip tmp', MYBACKUPDIR, $name);

	//exec($zip);
	echo 'okay';
}

